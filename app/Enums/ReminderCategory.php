<?php

namespace App\Enums;

/**
 * What a patient reminder is about.
 */
enum ReminderCategory: string
{
    use HasOptions;

    case Vaccination = 'vaccination';
    case Medication = 'medication';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Vaccination => 'Vaccination',
            self::Medication => 'Medication',
            self::FollowUp => 'Follow up',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Vaccination => 'info',
            self::Medication => 'warning',
            self::FollowUp => 'neutral',
        };
    }
}
