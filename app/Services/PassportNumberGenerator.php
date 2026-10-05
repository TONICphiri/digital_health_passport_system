<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Str;

/**
 * Creates the unique health passport number and QR token for a patient.
 *
 * Format: PREFIX-YEAR-SEQUENCE-CHECK, for example MW-2026-000123-4.
 * The final digit is a Luhn check digit, so a mistyped number is detected.
 */
class PassportNumberGenerator
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Must be called inside a database transaction so the lock holds.
     */
    public function nextPassportNumber(): string
    {
        $prefix = $this->settings->passportPrefix();
        $year = now()->format('Y');
        $pattern = "{$prefix}-{$year}-";

        $latest = Patient::query()
            ->where('passport_number', 'like', $pattern.'%')
            ->lockForUpdate()
            ->orderByDesc('passport_number')
            ->value('passport_number');

        $sequence = $latest ? ((int) explode('-', $latest)[2]) + 1 : 1;
        $body = str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);

        return $pattern.$body.'-'.$this->checkDigit($year.$body);
    }

    public function newQrToken(): string
    {
        do {
            $token = Str::random(48);
        } while (Patient::query()->where('qr_token', $token)->exists());

        return $token;
    }

    public function isValid(string $passportNumber): bool
    {
        $parts = explode('-', $passportNumber);

        if (count($parts) !== 4) {
            return false;
        }

        return $this->checkDigit($parts[1].$parts[2]) === $parts[3];
    }

    private function checkDigit(string $digits): string
    {
        $sum = 0;
        $double = true;

        for ($index = strlen($digits) - 1; $index >= 0; $index--) {
            $value = (int) $digits[$index];

            if ($double) {
                $value *= 2;
                $value = $value > 9 ? $value - 9 : $value;
            }

            $sum += $value;
            $double = ! $double;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
