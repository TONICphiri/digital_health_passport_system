<?php

namespace App\Support;

use App\Models\Vaccine;
use App\Services\SettingService;

/**
 * The values the offline visit forms need. Both the scan page and the cached
 * offline page read them from here, so nothing is typed in twice.
 */
class OfflineForms
{
    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        $settings = app(SettingService::class);

        return [
            'keepMinutes' => $settings->passportSessionMinutes(),
            'draftHours' => $settings->offlineDraftHours(),
            'frequencies' => $settings->list('dosage_frequencies'),
            'limits' => config('health_passport.vital_limits'),
            'maxMedications' => config('health_passport.max_medications'),
            'vaccines' => Vaccine::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
