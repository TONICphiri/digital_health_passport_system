<?php

namespace Tests\Feature;

use App\Enums\AccessMethod;
use App\Models\Encounter;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rule that defines the system: a passport is closed until the card is
 * scanned, and every opening is recorded.
 */
class PassportScanFlowTest extends TestCase
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

    private function scanned(Patient $patient): string
    {
        return 'DHP:'.$patient->qr_token;
    }

    public function test_a_passport_cannot_be_read_before_it_is_scanned(): void
    {
        $this->actingAs($this->worker())->get(route('patients.show', $this->patient()))->assertForbidden();
    }

    public function test_scanning_the_card_opens_the_passport_and_records_the_access(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->worker())
            ->post(route('patients.open'), ['code' => $this->scanned($patient)])
            ->assertRedirect(route('patients.show', $patient));

        $this->get(route('patients.show', $patient))->assertOk()->assertSee($patient->full_name);

        $this->assertDatabaseHas('passport_accesses', [
            'patient_id' => $patient->id,
            'user_id' => $this->worker()->id,
            'method' => AccessMethod::QrScan->value,
        ]);
    }

    public function test_a_code_that_is_not_a_passport_card_is_refused(): void
    {
        $this->actingAs($this->worker())
            ->post(route('patients.open'), ['code' => 'https://example.com'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('passport_accesses', PassportAccess::query()->count());
    }

    public function test_without_the_card_the_date_of_birth_must_match(): void
    {
        $patient = $this->patient();
        $before = PassportAccess::query()->count();

        $this->actingAs($this->worker())
            ->post(route('patients.open'), ['identifier' => $patient->passport_number, 'date_of_birth' => '1990-01-01'])
            ->assertSessionHas('error');
        $this->assertSame($before, PassportAccess::query()->count());

        $this->post(route('patients.open'), ['identifier' => $patient->passport_number, 'date_of_birth' => $patient->date_of_birth->toDateString()])
            ->assertRedirect(route('patients.show', $patient));

        $this->assertDatabaseHas('passport_accesses', ['patient_id' => $patient->id, 'method' => AccessMethod::PassportNumber->value, 'closed_at' => null]);
    }

    public function test_an_expired_or_closed_passport_is_locked_again(): void
    {
        $patient = $this->patient();
        $this->actingAs($this->worker())->post(route('patients.open'), ['code' => $this->scanned($patient)]);
        $access = PassportAccess::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();

        $this->delete(route('patients.close', $access))->assertRedirect(route('patients.scan'));
        $this->get(route('patients.show', $patient))->assertForbidden();

        $this->post(route('patients.open'), ['code' => $this->scanned($patient)]);
        PassportAccess::query()->open()->update(['expires_at' => now()->subMinute()]);
        $this->get(route('patients.show', $patient))->assertForbidden();
    }

    public function test_an_encounter_can_only_be_recorded_in_an_open_passport(): void
    {
        $patient = $this->patient();
        $payload = [
            'reason' => 'Cough', 'diagnosis' => 'Chest infection', 'treatment_summary' => 'Rest',
            'follow_up_on' => now()->addDays(7)->toDateString(),
            'medications' => [['name' => 'Amoxicillin', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration_days' => 5]],
        ];

        $this->actingAs($this->worker())->post(route('encounters.store', $patient), $payload)->assertForbidden();
        $this->assertDatabaseMissing('encounters', ['reason' => 'Cough']);

        $this->post(route('patients.open'), ['code' => $this->scanned($patient)]);
        $this->post(route('encounters.store', $patient), $payload)->assertRedirect(route('patients.show', $patient));

        $encounter = Encounter::query()->where('reason', 'Cough')->firstOrFail();
        $this->assertSame('Amoxicillin', $encounter->medications->first()->name);
        $this->assertTrue(Reminder::query()->where('patient_id', $patient->id)->where('title', 'Follow up visit')->exists());
    }

    public function test_vaccinations_and_reminders_also_need_an_open_passport(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->worker());
        $this->get(route('vaccinations.create', $patient))->assertForbidden();
        $this->get(route('reminders.create', $patient))->assertForbidden();

        $this->post(route('patients.open'), ['code' => $this->scanned($patient)]);
        $this->get(route('vaccinations.create', $patient))->assertOk();
        $this->get(route('reminders.create', $patient))->assertOk();
    }

    public function test_administrators_cannot_open_passports(): void
    {
        $patient = $this->patient();

        foreach (['admin@healthpassport.mw', 'facility@healthpassport.mw'] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->actingAs($user)->post(route('patients.open'), ['code' => $this->scanned($patient)])->assertForbidden();
            $this->get(route('patients.show', $patient))->assertForbidden();
        }
    }

    public function test_a_child_can_only_be_linked_through_the_mothers_open_passport(): void
    {
        $mother = Patient::query()->where('national_id', 'KT7Y4M21')->firstOrFail();
        $child = [
            'first_name' => 'Ruth', 'last_name' => 'Banda', 'date_of_birth' => now()->subMonths(2)->toDateString(), 'sex' => 'female',
            'district_id' => $mother->district_id, 'mother_id' => $mother->id,
            'contacts' => [['full_name' => 'Grace Banda', 'relationship' => 'Mother', 'phone' => '+265 991 204 118']],
        ];

        $this->actingAs($this->worker())->post(route('patients.store'), $child)->assertSessionHas('error');
        $this->assertDatabaseMissing('patients', ['first_name' => 'Ruth']);

        $this->post(route('patients.open'), ['code' => $this->scanned($mother)]);
        $this->post(route('patients.store'), $child)->assertRedirect();
        $this->assertDatabaseHas('patients', ['first_name' => 'Ruth', 'mother_id' => $mother->id]);
    }

    public function test_the_holder_can_see_who_opened_their_passport(): void
    {
        $holder = User::query()->where('email', 'patient@healthpassport.mw')->firstOrFail();
        $mother = $holder->patient;

        $this->actingAs($this->worker())->post(route('patients.open'), ['code' => $this->scanned($mother)]);

        $this->actingAs($holder)->get(route('portal.records'))->assertOk()->assertSee($this->worker()->name);
    }

    public function test_a_facility_administrator_creates_health_workers_only(): void
    {
        $admin = User::query()->where('email', 'facility@healthpassport.mw')->firstOrFail();

        $this->actingAs($admin)->post(route('facility.staff.store'), [
            'name' => 'New Worker', 'email' => 'new.worker@healthpassport.mw', 'phone' => '+265 999 000 111', 'job_title' => 'Nurse',
        ])->assertRedirect();

        $this->assertTrue(User::query()->where('email', 'new.worker@healthpassport.mw')->firstOrFail()->hasRole('health_worker'));
    }
}
