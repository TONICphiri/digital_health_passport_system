<?php

namespace Tests\Feature;

use App\Enums\VisitStageType;
use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The staged visit workflow: reception, triage, consultation, dispensing.
 * Each stage is credited to the worker who records it, and the visit only
 * locks once the pharmacist has handed over every prescribed medicine.
 */
class VisitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function clerk(): User
    {
        return User::query()->where('email', 'clerk@healthpassport.mw')->firstOrFail();
    }

    private function nurse(): User
    {
        return User::query()->where('email', 'healthworker2@healthpassport.mw')->firstOrFail();
    }

    private function doctor(): User
    {
        return User::query()->where('email', 'healthworker@healthpassport.mw')->firstOrFail();
    }

    private function pharmacist(): User
    {
        return User::query()->where('email', 'pharmacist@healthpassport.mw')->firstOrFail();
    }

    /** @return array<string, User> */
    private function workers(): array
    {
        return [
            'clerk' => $this->clerk(),
            'nurse' => $this->nurse(),
            'doctor' => $this->doctor(),
            'pharmacist' => $this->pharmacist(),
        ];
    }

    private function patient(string $nationalId = 'PL2Q8X55'): Patient
    {
        return Patient::query()->where('national_id', $nationalId)->firstOrFail();
    }

    private function open(Patient $patient): void
    {
        $this->post(route('patients.open'), ['code' => 'DHP:'.$patient->qr_token]);
    }

    public function test_a_full_visit_locks_only_after_every_medicine_is_handed_over(): void
    {
        [$clerk, $nurse, $doctor, $pharmacist] = array_values($this->workers());
        $patient = $this->patient();

        $this->actingAs($clerk);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'reception']), ['reason' => 'Chest pain', 'temperature' => 37.2])
            ->assertRedirect(route('patients.show', $patient));

        $visit = Visit::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $this->assertSame(VisitStatus::InProgress, $visit->status);
        $this->assertSame($clerk->id, $visit->stage(VisitStageType::Reception)->recorded_by);
        $this->assertSame($clerk->position->value, $visit->stage(VisitStageType::Reception)->recorded_as_position);

        $this->actingAs($nurse);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'triage']), [
            'symptoms' => 'Tight chest', 'tests' => [['name' => 'Malaria RDT', 'result' => 'Negative']],
        ])->assertRedirect(route('patients.show', $patient));

        $visit->refresh();
        $this->assertSame($nurse->id, $visit->stage(VisitStageType::Triage)->recorded_by);
        $this->assertSame(VisitStatus::InProgress, $visit->status);

        $this->actingAs($doctor);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'consultation']), [
            'diagnosis' => 'Hypertension', 'treatment_summary' => 'Rest',
            'follow_up_on' => now()->addWeeks(2)->toDateString(),
            'medications' => [
                ['name' => 'Amlodipine', 'dosage' => '5 mg', 'frequency' => 'Once daily', 'duration_days' => 30],
                ['name' => 'Aspirin', 'dosage' => '75 mg', 'frequency' => 'Once daily', 'duration_days' => 30],
            ],
        ])->assertRedirect(route('patients.show', $patient));

        $visit->refresh();
        $this->assertSame(VisitStatus::AwaitingDispense, $visit->status);
        $this->assertSame(2, $visit->medications()->count());
        $this->assertSame(0, $visit->medications()->where('dispensed', true)->count());

        $this->actingAs($pharmacist);
        $this->open($patient);
        $this->get(route('visits.dispense', [$patient, $visit]))->assertOk();

        $first = $visit->medications()->first();
        $second = $visit->medications()->whereKeyNot($first->id)->firstOrFail();
        $this->post(route('visits.dispense.store', [$patient, $visit]), ['medications' => [$first->id]])
            ->assertRedirect(route('patients.show', $patient));

        $visit->refresh();
        $this->assertSame(VisitStatus::AwaitingDispense, $visit->status);
        $this->assertTrue($visit->medications()->whereKey($first->id)->first()->dispensed);
        $this->assertSame($pharmacist->id, $visit->medications()->whereKey($first->id)->first()->dispensed_by);

        $this->post(route('visits.dispense.store', [$patient, $visit]), ['medications' => [$second->id]])
            ->assertRedirect(route('patients.show', $patient));

        $visit->refresh();
        $this->assertSame(VisitStatus::Completed, $visit->status);
        $this->assertNotNull($visit->locked_at);
        $this->assertSame($pharmacist->id, $visit->completed_by);
        $this->assertSame($pharmacist->id, $visit->stage(VisitStageType::Dispensing)->recorded_by);
        $this->assertSame('Handed over 2 of 2 medicines.', $visit->stage(VisitStageType::Dispensing)->summary);

        // A completed visit cannot be dispensed again.
        $this->post(route('visits.dispense.store', [$patient, $visit]), ['medications' => [$first->id]])
            ->assertForbidden();
    }

    public function test_vital_signs_keep_the_newest_reading_and_skip_a_repeat(): void
    {
        $clerk = $this->clerk();
        $nurse = $this->nurse();
        $patient = $this->patient();

        $this->actingAs($clerk);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'reception']), ['reason' => 'Fever', 'temperature' => 38.0]);

        $this->actingAs($nurse);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'triage']), [
            'symptoms' => 'Fever', 'temperature' => 38.0, 'weight' => 20,
        ]);

        $visit = Visit::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $this->assertSame(2, $visit->observations()->count());
        $this->assertSame('38', $visit->latestObservation('temperature')->value);
        $this->assertSame('20', $visit->latestObservation('weight')->value);
    }

    public function test_a_consultation_without_medicines_completes_the_visit_at_once(): void
    {
        $doctor = $this->doctor();
        $pharmacist = $this->pharmacist();
        $patient = $this->patient();

        $this->actingAs($doctor);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'consultation']), [
            'diagnosis' => 'Uncomplicated malaria', 'treatment_summary' => 'Rest and fluids',
        ])->assertRedirect(route('patients.show', $patient));

        $visit = Visit::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $this->assertSame(VisitStatus::Completed, $visit->status);
        $this->assertNotNull($visit->locked_at);
        $this->assertSame($doctor->id, $visit->completed_by);
        $this->assertFalse($visit->medications()->exists());

        $this->actingAs($pharmacist);
        $this->get(route('visits.dispense', [$patient, $visit]))->assertForbidden();
    }

    public function test_changing_a_stage_keeps_the_first_recorder_and_marks_the_editor(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient();
        $followUp = now()->addWeek()->toDateString();

        $this->actingAs($doctor);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'consultation']), [
            'diagnosis' => 'Toothache', 'treatment_summary' => 'Painkillers', 'follow_up_on' => $followUp,
            'medications' => [['name' => 'Paracetamol', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration_days' => 3]],
        ]);

        $this->post(route('visits.stage.store', [$patient, 'consultation']), [
            'diagnosis' => 'Abscess', 'treatment_summary' => 'Antibiotics', 'follow_up_on' => $followUp,
            'medications' => [['name' => 'Amoxicillin', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration_days' => 5]],
        ]);

        $visit = Visit::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $stage = $visit->stage(VisitStageType::Consultation);

        $this->assertSame(1, $visit->stages()->where('type', VisitStageType::Consultation)->count());
        $this->assertSame($doctor->id, $stage->recorded_by);
        $this->assertSame($doctor->id, $stage->edited_by);
        $this->assertNotNull($stage->edited_at);
        $this->assertSame('Abscess', $stage->summary);

        $names = $visit->medications()->pluck('name')->all();
        $this->assertSame(['Amoxicillin'], $names);
    }

    public function test_every_station_is_kept_to_its_own_stage(): void
    {
        $clerk = $this->clerk();
        $doctor = $this->doctor();
        $patient = $this->patient();

        $this->actingAs($clerk);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'reception']), ['reason' => 'Cough']);

        foreach (['healthworker2@healthpassport.mw' => 'consultation', 'pharmacist@healthpassport.mw' => 'consultation', 'healthworker@healthpassport.mw' => 'reception'] as $email => $type) {
            $worker = User::query()->where('email', $email)->firstOrFail();
            $this->actingAs($worker);
            $this->open($patient);

            $this->get(route('visits.stage.create', [$patient, $type]))->assertForbidden();
            $this->post(route('visits.stage.store', [$patient, $type]), $this->validPayloadFor($type))->assertForbidden();
        }

        $this->assertSame(1, Visit::query()->where('patient_id', $patient->id)->count());
    }

    /** @return array<string, mixed> */
    private function validPayloadFor(string $type): array
    {
        return match ($type) {
            'reception' => ['reason' => 'Cough'],
            'triage' => ['symptoms' => 'Fever', 'tests' => []],
            'consultation' => ['diagnosis' => 'Fever', 'treatment_summary' => 'Rest', 'medications' => []],
            default => [],
        };
    }

    public function test_the_patient_portal_shows_the_visit_and_who_recorded_each_stage(): void
    {
        [$clerk, $nurse, $doctor] = array_values($this->workers());
        $patient = $this->patient('KT7Y4M21');

        $this->actingAs($clerk);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'reception']), ['reason' => 'Headache']);

        $this->actingAs($nurse);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'triage']), ['symptoms' => 'Dizziness']);

        $this->actingAs($doctor);
        $this->open($patient);
        $this->post(route('visits.stage.store', [$patient, 'consultation']), ['diagnosis' => 'Anaemia']);

        $visit = Visit::query()->where('patient_id', $patient->id)->latest('id')->firstOrFail();
        $this->assertSame(VisitStatus::Completed, $visit->status);

        $holder = User::query()->where('email', 'patient@healthpassport.mw')->firstOrFail();
        $this->actingAs($holder)
            ->get(route('portal.records'))
            ->assertOk()
            ->assertSee('Visits')
            ->assertSee('Reception')
            ->assertSee($clerk->name)
            ->assertSee($nurse->name)
            ->assertSee($doctor->name);
    }
}