<?php

namespace App\Enums;

/**
 * The station a health worker works at. The system keeps four roles; the
 * position decides which stage of a visit a health worker may record.
 */
enum Position: string
{
    use HasOptions;

    case Clerk = 'clerk';
    case Nurse = 'nurse';
    case Doctor = 'doctor';
    case Pharmacist = 'pharmacist';

    public function label(): string
    {
        return match ($this) {
            self::Clerk => 'Front desk',
            self::Nurse => 'Nurse',
            self::Doctor => 'Doctor',
            self::Pharmacist => 'Pharmacist',
        };
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
