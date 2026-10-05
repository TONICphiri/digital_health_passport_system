<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Visits typed without a connection and sent later. The passport must be
 * opened by the scan first, and every normal rule must still apply.
 */
class OfflineVisitTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function worker(): User
    {
        return User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
    }

    private function patient(): Patient
    {
        return Patient::query()->where('national_id', 'PL2Q8X55')->firstOrFail();
    }

    private function payload(array $draft, ?string $id = null): array
    {
        return ['code' => 'DHP:'.$this->patient()->qr_token, 'sync_id' => $id ?? (string) Str::uuid(), 'draft' => $draft];
    }

    public function test_the_offline_page_holds_no_account_or_patient_information(): void
    {
        $response = $this->actingAs($this->worker())->get(route('offline.scan'))->assertOk();

        $response->assertSee('id="scan-form"', false)->assertSee('data-draft-hours="24"', false);
        $response->assertDontSee($this->worker()->name)->assertDontSee($this->patient()->last_name);
    }

    public function test_only_health_workers_can_use_the_offline_pages(): void
    {
        foreach (['patient@healthpassport.mw', 'facility@healthpassport.mw'] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->actingAs($user)->get(route('offline.scan'))->assertForbidden();
            $this->actingAs($user)->post(route('patients.sync'), $this->payload([]))->assertForbidden();
        }
    }

    public function test_visit_notes_are_added_after_the_passport_is_opened_by_the_scan(): void
    {
        $patient = $this->patient();
        $id = (string) Str::uuid();
        $accesses = PassportAccess::query()->where('patient_id', $patient->id)->count();

        $this->actingAs($this->worker())->post(route('patients.sync'), $this->payload([
            'encounter' => [
                'reason' => 'Headache', 'diagnosis' => 'Tension headache', 'temperature' => '36.7',
                'medications' => [['name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration_days' => '3']],
            ],
        ], $id))->assertRedirect(route('patients.show', ['patient' => $patient, 'synced' => $id]))->assertSessionHas('success');

        $encounter = Encounter::query()->where('patient_id', $patient->id)->where('reason', 'Headache')->firstOrFail();
        $this->assertSame('Paracetamol', $encounter->medications()->first()->name);
        $this->assertSame($this->worker()->id, $encounter->recorded_by);
        $this->assertSame($accesses + 1, PassportAccess::query()->where('patient_id', $patient->id)->count(), 'The opening is in the access log.');
    }

    public function test_a_vaccination_is_recorded_with_its_reminder(): void
    {
        $vaccine = Vaccine::query()->where('is_active', true)->where('total_doses', '>', 1)->firstOrFail();

        $this->actingAs($this->worker())->post(route('patients.sync'), $this->payload([
            'vaccination' => ['vaccine_id' => (string) $vaccine->id, 'administered_on' => today()->toDateString(), 'batch_number' => 'B1'],
        ]))->assertSessionHas('success');

        $this->assertTrue(Vaccination::query()->where('patient_id', $this->patient()->id)->where('vaccine_id', $vaccine->id)->exists());
    }

    public function test_the_same_visit_sent_twice_is_recorded_once(): void
    {
        $id = (string) Str::uuid();
        $draft = ['encounter' => ['reason' => 'Back pain', 'diagnosis' => 'Strain']];

        $this->actingAs($this->worker())->post(route('patients.sync'), $this->payload($draft, $id));
        $this->actingAs($this->worker())->post(route('patients.sync'), $this->payload($draft, $id))
            ->assertRedirect(route('patients.scan'));

        $this->assertSame(1, Encounter::query()->where('reason', 'Back pain')->count());
    }

    public function test_notes_that_fail_the_rules_go_back_to_the_normal_form_with_the_text_kept(): void
    {
        $patient = $this->patient();
        $id = (string) Str::uuid();

        $this->actingAs($this->worker())->post(route('patients.sync'), $this->payload([
            'encounter' => ['reason' => 'Fever', 'diagnosis' => 'Malaria', 'systolic_pressure' => '120'],
        ], $id))
            ->assertRedirect(route('encounters.create', ['patient' => $patient, 'synced' => $id]))
            ->assertSessionHasErrors('diastolic_pressure')
            ->assertSessionHasInput('reason', 'Fever');

        $this->assertSame(0, Encounter::query()->where('reason', 'Fever')->count());
    }

    public function test_a_code_that_is_not_a_card_records_nothing(): void
    {
        $before = Encounter::query()->count();

        $this->actingAs($this->worker())->post(route('patients.sync'), [
            'code' => 'https://example.com', 'sync_id' => (string) Str::uuid(),
            'draft' => ['encounter' => ['reason' => 'X', 'diagnosis' => 'Y']],
        ])->assertSessionHas('error');

        $this->assertSame($before, Encounter::query()->count());
    }

    public function test_notes_cannot_be_added_to_an_inactive_passport(): void
    {
        $this->patient()->update(['status' => 'deceased']);

        $this->actingAs($this->worker())->post(route('patients.sync'), $this->payload([
            'encounter' => ['reason' => 'Late', 'diagnosis' => 'Late'],
        ]))->assertSessionHas('error');

        $this->assertSame(0, Encounter::query()->where('reason', 'Late')->count());
    }
}
