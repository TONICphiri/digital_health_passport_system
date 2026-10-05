<?php

namespace App\Services;

use App\Models\Patient;

/**
 * Separates child records from the mother's profile once the child reaches
 * the separation age. Runs every day from the scheduler.
 */
class ChildSeparationService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @return int Number of records separated.
     */
    public function separateAdults(): int
    {
        $cutoff = today()->subYears($this->settings->childSeparationAge());
        $count = 0;

        Patient::query()
            ->whereNotNull('mother_id')
            ->whereDate('date_of_birth', '<=', $cutoff)
            ->chunkById(200, function ($patients) use (&$count) {
                foreach ($patients as $patient) {
                    $patient->update([
                        'mother_id' => null,
                        'separated_from_mother_at' => now(),
                    ]);

                    $this->audit->record(
                        'patient.separated',
                        "Separated {$patient->full_name} ({$patient->passport_number}) from the mother's profile on reaching adulthood.",
                        $patient,
                    );

                    $count++;
                }
            });

        return $count;
    }
}
