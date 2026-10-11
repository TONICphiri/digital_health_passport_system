<?php

namespace App\Services;

use App\Enums\ImmunisationStatus;
use App\Enums\PatientStatus;
use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Illuminate\Support\Collection;

/**
 * Works out whether each vaccine is due for a patient, based on their date of
 * birth and the doses already given, and turns overdue vaccines into reminders.
 */
class ImmunisationStatusService
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * Days a vaccine stays on time after its due date. System setting, 28 by
     * default.
     */
    public function graceDays(): int
    {
        return max(0, (int) $this->settings->get('immunisation_grace_days', '28'));
    }

    /**
     * The status of a single vaccine for a patient. Doses already given for
     * that vaccine may be passed in to avoid an extra query.
     *
     * @param  Collection<int, Vaccination>|null  $doses
     */
    public function for(Patient $patient, Vaccine $vaccine, ?Collection $doses = null): ImmunisationResult
    {
        $doses ??= $patient->vaccinations()->where('vaccine_id', $vaccine->id)->get();

        if ($doses->count() >= $vaccine->total_doses) {
            return new ImmunisationResult($vaccine, ImmunisationStatus::Complete);
        }

        if ($vaccine->recommended_age_days === null) {
            return new ImmunisationResult($vaccine, ImmunisationStatus::NoSchedule);
        }

        $lastDose = $doses->sortByDesc('administered_on')->first();

        if ($lastDose === null) {
            $dueOn = $patient->date_of_birth?->copy()->addDays((int) $vaccine->recommended_age_days);
        } elseif ($vaccine->days_between_doses === null) {
            return new ImmunisationResult($vaccine, ImmunisationStatus::NoSchedule);
        } else {
            $dueOn = $lastDose->administered_on->copy()->addDays((int) $vaccine->days_between_doses);
        }

        if ($dueOn === null) {
            return new ImmunisationResult($vaccine, ImmunisationStatus::NoSchedule);
        }

        $graceEndsOn = $dueOn->copy()->addDays($this->graceDays());
        $today = today();

        $status = match (true) {
            $today->greaterThan($graceEndsOn) => ImmunisationStatus::Overdue,
            $today->greaterThanOrEqualTo($dueOn) => ImmunisationStatus::Due,
            default => ImmunisationStatus::NotYetDue,
        };

        return new ImmunisationResult($vaccine, $status, $dueOn, $graceEndsOn);
    }

    /**
     * The status of every active vaccine for a patient, overdue first.
     *
     * @param  Collection<int, Vaccine>|null  $vaccines
     * @return Collection<int, ImmunisationResult>
     */
    public function allFor(Patient $patient, ?Collection $vaccines = null): Collection
    {
        $vaccines ??= Vaccine::query()->where('is_active', true)->orderBy('name')->get();
        $given = $patient->vaccinations()->get()->groupBy('vaccine_id');

        return $vaccines
            ->map(fn (Vaccine $vaccine) => $this->for($patient, $vaccine, $given->get($vaccine->id)))
            ->sortBy(fn (ImmunisationResult $result) => [$result->status->sortOrder(), $result->vaccine->name])
            ->values();
    }

    /**
     * Creates one active vaccination reminder per overdue vaccine. A vaccine
     * that already has an active reminder is left alone, so the daily run
     * never stacks duplicates. Returns the number of reminders created.
     */
    public function syncOverdueReminders(Patient $patient, ?User $createdBy = null): int
    {
        $created = 0;

        foreach ($this->allFor($patient)->where('status', ImmunisationStatus::Overdue) as $result) {
            $alreadyCovered = Reminder::query()
                ->where('patient_id', $patient->id)
                ->where('category', ReminderCategory::Vaccination)
                ->where('status', ReminderStatus::Active)
                ->where('source_type', $result->vaccine->getMorphClass())
                ->where('source_id', $result->vaccine->id)
                ->exists();

            if ($alreadyCovered) {
                continue;
            }

            Reminder::create([
                'patient_id' => $patient->id,
                'category' => ReminderCategory::Vaccination,
                'title' => "{$result->vaccine->name}, dose",
                'message' => "{$result->vaccine->name} ({$result->vaccine->protects_against}) is overdue. Visit your nearest health facility.",
                'due_on' => $result->dueOn,
                'status' => ReminderStatus::Active,
                'source_type' => $result->vaccine->getMorphClass(),
                'source_id' => $result->vaccine->id,
                'created_by' => $createdBy?->id,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Syncs overdue reminders for every active patient. Returns the total
     * number of reminders created.
     */
    public function syncAll(?User $createdBy = null): int
    {
        $created = 0;

        foreach (Patient::query()->where('status', PatientStatus::Active)->whereNotNull('date_of_birth')->cursor() as $patient) {
            $created += $this->syncOverdueReminders($patient, $createdBy);
        }

        return $created;
    }
}
