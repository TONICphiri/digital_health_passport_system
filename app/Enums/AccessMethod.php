<?php

namespace App\Enums;

/**
 * How a health worker proved the patient is present when opening a passport.
 */
enum AccessMethod: string
{
    use HasOptions;

    case QrScan = 'qr_scan';
    case PassportNumber = 'passport_number';
    case IdentityCheck = 'identity_check';
    case Registration = 'registration';

    public function label(): string
    {
        return match ($this) {
            self::QrScan => 'Card scanned',
            self::PassportNumber => 'Passport number and date of birth',
            self::IdentityCheck => 'National ID and date of birth',
            self::Registration => 'Issued at registration',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::QrScan, self::Registration => 'success',
            self::PassportNumber, self::IdentityCheck => 'warning',
        };
    }
}
