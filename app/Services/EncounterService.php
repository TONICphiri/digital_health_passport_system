<?php

namespace App\Services;

use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Adds an entry to a patient's passport. The caller has already checked that
 * the passport is open for this health worker.
 */
class EncounterService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $medications
     */
    public function record(Patient $patient, array $data, array $medications, User $worker): Encounter
    {
        return DB::transaction(function () use ($patient, $data, $medications, $worker) {
            $encounter = Encounter::create([
                ...$data,
                'patient_id' => $patient->id,
                'facility_id' => $worker->facility_id,
                'recorded_by' => $worker->id,
                'recorded_at' => now(),
            ]);

            foreach ($medications as $medication) {
                $encounter->medications()->create($medication);
            }

            if ($encounter->follow_up_on) {
                Reminder::create([
                    'patient_id' => $patient->id,
                    'category' => ReminderCategory::FollowUp,
                    'title' => 'Follow up visit',
                    'message' => "Please return to a health facility on {$encounter->follow_up_on->format('j F Y')} for your follow up.",
                    'due_on' => max(today(), $encounter->follow_up_on->copy()->subDay())->toDateString(),
                    'status' => ReminderStatus::Active,
                    'source_type' => $encounter->getMorphClass(),
                    'source_id' => $encounter->id,
                    'created_by' => $worker->id,
                ]);
            }

            $this->audit->record('encounter.recorded', "Added an encounter to the passport of {$patient->full_name}.", $encounter);

            return $encounter;
        });
    }
}
