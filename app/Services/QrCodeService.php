<?php

namespace App\Services;

use App\Models\Patient;
use SimpleSoftwareIO\QrCode\Generator;

/**
 * Builds the QR code printed on the health passport card. The code holds
 * only a random token, never personal or medical information, so a lost
 * card reveals nothing without access to the system.
 */
class QrCodeService
{
    public const PREFIX = 'DHP:';

    public function forPatient(Patient $patient, int $size = 160): string
    {
        $svg = (string) (new Generator)
            ->format('svg')
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate(self::PREFIX.$patient->qr_token);

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    /**
     * QR code for any address, used on vaccination certificates.
     */
    public function forUrl(string $url, int $size = 120): string
    {
        $svg = (string) (new Generator)
            ->format('svg')
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($url);

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    /**
     * Returns the token from a scanned value, or null if it is not ours.
     */
    public function tokenFromScan(string $value): ?string
    {
        $value = trim($value);

        return str_starts_with($value, self::PREFIX) ? substr($value, strlen(self::PREFIX)) : null;
    }
}
