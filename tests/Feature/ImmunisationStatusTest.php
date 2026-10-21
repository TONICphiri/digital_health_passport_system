<?php

namespace Tests\Feature;

use App\Enums\ImmunisationStatus;
use App\Enums\PatientStatus;
use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Enums\Sex;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use App\Services\ImmunisationStatusService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Age based immunisation status on the passport: when each vaccine is due,
 * whether it is running late, and the reminders that follow an overdue vaccine.
 */
class ImmunisationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function patient(Carbon $dateOfBirth): Patient
    {
        return Patient::create([
            'passport_number' => 'MW-TEST-'.Str::upper(Str::random(6)),
            'qr_token' => Str::random(32),
            'first_name' => 'Test',
            'last_name' => 'Holder',
            'date_of_birth' => $dateOfBirth->toDateString(),
            'sex' => Sex::Male,
            'status' => PatientStatus::Active,
        ]);
    }

    private function vaccine(array $overrides = []): Vaccine
    {
        return Vaccine::create([...[
            'name' => 'Test Vaccine '.Str::upper(Str::random(5)),
            'protects_against' => 'A test disease',
            'total_doses' => 1,
            'days_between_doses' => null,
            'recommended_age' => null,
            'recommended_age_days' => 0,
            'is_active' => true,
        ], ...$overrides]);
    }

    private function dose(Patient $patient, Vaccine $vaccine, Carbon $givenOn, int $doseNumber = 1): Vaccination
    {
        return Vaccination::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccine->id,
            'facility_id' => Facility::query()->orderBy('id')->value('id'),
            'dose_number' => $doseNumber,
            'administered_on' => $givenOn->toDateString(),
        ]);
    }

    private function service(): ImmunisationStatusService
    {
        return app(ImmunisationStatusService::class);
    }

    /**
     * The kind of reminder the daily run raises for an overdue vaccine.
     */
    private function reminderFor(Patient $patient, Vaccine $vaccine): Reminder
    {
        return Reminder::create([
            'patient_id' => $patient->id,
            'category' => ReminderCategory::Vaccination,
            'title' => "{$vaccine->name}, dose",
            'message' => "{$vaccine->name} is overdue. Visit your nearest health facility.",
            'due_on' => Carbon::today()->subDays(30)->toDateString(),
            'status' => ReminderStatus::Active,
            'source_type' => $vaccine->getMorphClass(),
            'source_id' => $vaccine->id,
        ]);
    }

    /**
     * allFor() still walks the whole catalogue, so tests that look across
     * every active vaccine first clear the seeded ages.
     */
    private function clearSeededSchedules(): void
    {
        Vaccine::query()->update(['recommended_age_days' => null]);
    }

    public function test_a_vaccine_without_an_age_is_no_schedule(): void
    {
        $vaccine = $this->vaccine(['recommended_age_days' => null]);

        $result = $this->service()->for($this->patient(Carbon::today()->subDays(30)), $vaccine);

        $this->assertSame(ImmunisationStatus::NoSchedule, $result->status);
        $this->assertNull($result->dueOn);
    }

    public function test_a_child_born_yesterday_is_not_yet_due_for_a_six_week_vaccine(): void
    {
        $vaccine = $this->vaccine(['total_doses' => 3, 'days_between_doses' => 28, 'recommended_age_days' => 42]);

        $result = $this->service()->for($this->patient(Carbon::today()->subDay()), $vaccine);

        $this->assertSame(ImmunisationStatus::NotYetDue, $result->status);
        $this->assertSame(Carbon::today()->addDays(41)->toDateString(), $result->dueOn->toDateString());
    }

    public function test_a_patient_with_no_vaccinations_is_due_when_the_age_is_reached(): void
    {
        $patient = $this->patient(Carbon::today()->subDays(40));
        $vaccine = $this->vaccine(['recommended_age_days' => 40]);

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::Due, $result->status);
        $this->assertSame($patient->date_of_birth->copy()->addDays(40)->toDateString(), $result->dueOn->toDateString());
    }

    public function test_a_patient_with_no_vaccinations_is_overdue_after_the_grace_period(): void
    {
        app(SettingService::class)->update(['immunisation_grace_days' => '10']);

        $patient = $this->patient(Carbon::today()->subDays(60));
        $vaccine = $this->vaccine(['recommended_age_days' => 0]);

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::Overdue, $result->status);
        $this->assertSame($patient->date_of_birth->copy()->addDays(10)->toDateString(), $result->graceEndsOn->toDateString());
    }

    public function test_a_partially_vaccinated_patient_is_not_yet_due_for_the_next_dose(): void
    {
        $vaccine = $this->vaccine(['total_doses' => 3, 'days_between_doses' => 28, 'recommended_age_days' => 42]);
        $patient = $this->patient(Carbon::today()->subYear());

        $this->dose($patient, $vaccine, Carbon::today());

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::NotYetDue, $result->status);
        $this->assertSame(Carbon::today()->addDays(28)->toDateString(), $result->dueOn->toDateString());
    }

    public function test_a_partially_vaccinated_patient_is_due_for_the_next_dose(): void
    {
        $vaccine = $this->vaccine(['total_doses' => 3, 'days_between_doses' => 28, 'recommended_age_days' => 42]);
        $patient = $this->patient(Carbon::today()->subYear());

        $this->dose($patient, $vaccine, Carbon::today()->subDays(28));

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::Due, $result->status);
        $this->assertSame(Carbon::today()->toDateString(), $result->dueOn->toDateString());
    }

    public function test_a_partially_vaccinated_patient_is_overdue_after_the_grace_period(): void
    {
        app(SettingService::class)->update(['immunisation_grace_days' => '10']);

        $vaccine = $this->vaccine(['total_doses' => 3, 'days_between_doses' => 28, 'recommended_age_days' => 42]);
        $patient = $this->patient(Carbon::today()->subYear());

        $this->dose($patient, $vaccine, Carbon::today()->subDays(60));

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::Overdue, $result->status);
        $this->assertSame(Carbon::today()->subDays(32)->addDays(10)->toDateString(), $result->graceEndsOn->toDateString());
    }

    public function test_a_fully_vaccinated_patient_is_complete(): void
    {
        $vaccine = $this->vaccine(['total_doses' => 2, 'days_between_doses' => 28, 'recommended_age_days' => 42]);
        $patient = $this->patient(Carbon::today()->subYear());

        $this->dose($patient, $vaccine, Carbon::today()->subMonths(2), 1);
        $this->dose($patient, $vaccine, Carbon::today()->subMonths(2)->addDays(28), 2);

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::Complete, $result->status);
        $this->assertNull($result->dueOn);
    }

    public function test_an_overdue_vaccine_creates_one_active_reminder_only(): void
    {
        $patient = $this->patient(Carbon::today()->subDays(200));
        $first = $this->vaccine(['total_doses' => 2, 'days_between_doses' => 28, 'recommended_age_days' => 0]);
        $second = $this->vaccine(['total_doses' => 2, 'days_between_doses' => 28, 'recommended_age_days' => 0]);
        $neverGiven = $this->vaccine(['recommended_age_days' => 0]);

        // One dose each makes them assigned, and the second dose is long late.
        $this->dose($patient, $first, $patient->date_of_birth);
        $this->dose($patient, $second, $patient->date_of_birth);

        $this->assertSame(2, $this->service()->syncOverdueReminders($patient));
        $this->assertSame(0, $this->service()->syncOverdueReminders($patient));

        $reminders = Reminder::query()->where('patient_id', $patient->id)->get();
        $this->assertCount(2, $reminders);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $reminders->pluck('source_id')->all());
        $this->assertFalse($reminders->pluck('source_id')->contains($neverGiven->id));
        $this->assertSame(ReminderCategory::Vaccination, $reminders[0]->category);
        $this->assertSame(ReminderStatus::Active, $reminders[0]->status);
        $this->assertSame($patient->date_of_birth->copy()->addDays(28)->toDateString(), $reminders[0]->due_on->toDateString());
    }

    public function test_only_vaccines_with_a_recorded_dose_are_assigned_to_the_patient(): void
    {
        $patient = $this->patient(Carbon::today()->subDays(200));
        $given = $this->vaccine(['total_doses' => 2, 'days_between_doses' => 28, 'recommended_age_days' => 0]);
        $neverGiven = $this->vaccine(['recommended_age_days' => 0]);

        $this->dose($patient, $given, $patient->date_of_birth);

        $assignedIds = $this->service()->assignedVaccines($patient)->pluck('id');

        $this->assertTrue($assignedIds->contains($given->id));
        $this->assertFalse($assignedIds->contains($neverGiven->id));

        $results = $this->service()->assignedFor($patient);

        $this->assertCount(1, $results);
        $this->assertSame($given->id, $results->first()->vaccine->id);
        $this->assertSame(ImmunisationStatus::Overdue, $results->first()->status);
    }

    public function test_a_vaccine_that_was_never_given_creates_no_reminder(): void
    {
        $patient = $this->patient(Carbon::today()->subDays(200));
        $this->vaccine(['recommended_age_days' => 0]);

        $this->assertTrue($this->service()->assignedFor($patient)->isEmpty());
        $this->assertSame(0, $this->service()->syncOverdueReminders($patient));
        $this->assertSame(0, Reminder::query()->where('patient_id', $patient->id)->count());
    }

    public function test_a_reminder_for_a_vaccine_that_was_never_given_is_cancelled(): void
    {
        $patient = $this->patient(Carbon::today()->subDays(200));
        $neverGiven = $this->vaccine();
        $given = $this->vaccine();

        $this->dose($patient, $given, Carbon::today()->subDays(10));

        $stale = $this->reminderFor($patient, $neverGiven);
        $assigned = $this->reminderFor($patient, $given);
        $manual = Reminder::create([
            'patient_id' => $patient->id,
            'category' => ReminderCategory::Vaccination,
            'title' => 'Catch up visit',
            'message' => 'Come to the clinic for a checkup.',
            'due_on' => Carbon::today()->addDays(5)->toDateString(),
            'status' => ReminderStatus::Active,
        ]);

        $this->assertSame(1, $this->service()->cancelUnassignedReminders($patient));
        $this->assertSame(0, $this->service()->cancelUnassignedReminders($patient));

        $this->assertSame(ReminderStatus::Cancelled, $stale->fresh()->status);
        $this->assertSame(ReminderStatus::Active, $assigned->fresh()->status);
        $this->assertSame(ReminderStatus::Active, $manual->fresh()->status);
    }

    public function test_a_vaccine_still_within_grace_is_not_flagged_overdue(): void
    {
        $this->clearSeededSchedules();
        $patient = $this->patient(Carbon::today()->subDays(200));
        $vaccine = $this->vaccine(['recommended_age_days' => 180]);

        $result = $this->service()->for($patient, $vaccine);

        $this->assertSame(ImmunisationStatus::Due, $result->status);
        $this->assertTrue($this->service()->allFor($patient)->pluck('status')->doesntContain(ImmunisationStatus::Overdue));
        $this->assertSame(0, $this->service()->syncOverdueReminders($patient));
    }

    public function test_the_administrator_can_set_the_age_in_days_and_it_serves_the_calendar(): void
    {
        $admin = User::query()->role('system_admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.vaccines.store'), [
            'name' => 'Polio Booster',
            'protects_against' => 'Poliomyelitis',
            'total_doses' => '1',
            'days_between_doses' => null,
            'recommended_age' => 'At birth',
            'recommended_age_days' => '0',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $vaccine = Vaccine::query()->where('name', 'Polio Booster')->firstOrFail();
        $this->assertSame(0, $vaccine->recommended_age_days);

        $this->actingAs($admin)->get(route('admin.vaccines.index'))
            ->assertOk()->assertSee('Polio Booster')->assertSee('Age (days)');

        $this->actingAs($admin)->post(route('admin.vaccines.store'), [
            'name' => 'Bad Age',
            'protects_against' => 'Nothing',
            'total_doses' => '1',
            'recommended_age_days' => '-1',
        ])->assertSessionHasErrors('recommended_age_days');
    }

    public function test_the_portal_shows_the_immunisation_status_of_each_vaccine(): void
    {
        $holder = User::query()->role('patient')->firstOrFail();
        $patient = $holder->patient;

        // Assigned vaccines only: one with no age set, one whose next dose
        // was due long ago.
        $noAge = $this->vaccine(['total_doses' => 2, 'days_between_doses' => 28, 'recommended_age_days' => null]);
        $late = $this->vaccine(['total_doses' => 2, 'days_between_doses' => 10, 'recommended_age_days' => 0]);

        $this->dose($patient, $noAge, Carbon::today()->subMonths(6));
        $this->dose($patient, $late, Carbon::today()->subDays(100));

        $this->actingAs($holder)->get(route('portal.records'))
            ->assertOk()
            ->assertSee('Immunisation status')
            ->assertSee('No schedule set')
            ->assertSee('Overdue');
    }

    public function test_the_portal_does_not_show_vaccines_that_were_never_given(): void
    {
        $holder = User::query()->role('patient')->firstOrFail();

        // The seeded holder is old enough for several catalogue vaccines to be
        // past their age, but none of them were ever given, so the portal must
        // not call them overdue.
        $this->actingAs($holder)->get(route('portal.records'))
            ->assertOk()
            ->assertDontSee('Immunisation status')
            ->assertDontSee('Overdue');
    }
}
