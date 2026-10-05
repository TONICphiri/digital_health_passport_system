<?php

namespace App\Services;

use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Exceptions\WorkflowException;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccination;
use App\Models\Vaccine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records vaccine doses and schedules a reminder for the next dose.
 */
class VaccinationService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @param  array<string, mixed>  $data  administered_on, batch_number, notes
     */
    public function record(Patient $patient, Vaccine $vaccine, array $data, User $staff): Vaccination
    {
        $dosesGiven = $patient->vaccinations()->where('vaccine_id', $vaccine->id)->count();

        if ($dosesGiven >= $vaccine->total_doses) {
            throw new WorkflowException("{$patient->full_name} has already received all {$vaccine->total_doses} doses of {$vaccine->name}.");
        }

        $doseNumber = $dosesGiven + 1;
        $administeredOn = Carbon::parse($data['administered_on']);
        $nextDue = ($doseNumber < $vaccine->total_doses && $vaccine->days_between_doses)
            ? $administeredOn->copy()->addDays($vaccine->days_between_doses)
            : null;

        return DB::transaction(function () use ($patient, $vaccine, $data, $staff, $doseNumber, $administeredOn, $nextDue) {
            Reminder::query()
                ->where('patient_id', $patient->id)
                ->where('source_type', (new Vaccine)->getMorphClass())
                ->where('source_id', $vaccine->id)
                ->where('status', ReminderStatus::Active)
                ->update(['status' => ReminderStatus::Completed]);

            $vaccination = Vaccination::create([
                'patient_id' => $patient->id,
                'vaccine_id' => $vaccine->id,
                'facility_id' => $staff->facility_id,
                'administered_by' => $staff->id,
                'dose_number' => $doseNumber,
                'administered_on' => $administeredOn->toDateString(),
                'batch_number' => $data['batch_number'] ?? null,
                'next_dose_due_on' => $nextDue?->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($nextDue) {
                Reminder::create([
                    'patient_id' => $patient->id,
                    'category' => ReminderCategory::Vaccination,
                    'title' => "{$vaccine->name}, dose ".($doseNumber + 1),
                    'message' => "Dose ".($doseNumber + 1)." of {$vaccine->name} is due on {$nextDue->format('j F Y')}. Please visit your nearest health facility.",
                    'due_on' => $nextDue->toDateString(),
                    'status' => ReminderStatus::Active,
                    'source_type' => $vaccine->getMorphClass(),
                    'source_id' => $vaccine->id,
                    'created_by' => $staff->id,
                ]);
            }

            $this->audit->record('vaccination.recorded', "Recorded {$vaccine->name} dose {$doseNumber} for {$patient->full_name}.", $vaccination);

            return $vaccination;
        });
    }
}
