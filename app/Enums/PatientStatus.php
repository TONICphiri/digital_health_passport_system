<?php

namespace App\Enums;

/**
 * Life and record status of a patient.
 */
enum PatientStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Deceased = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Deceased => 'Deceased',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'neutral',
            self::Deceased => 'danger',
        };
    }
}
