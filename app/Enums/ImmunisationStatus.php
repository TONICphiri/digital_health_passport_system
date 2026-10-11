<?php

namespace App\Enums;

/**
 * Where a vaccine stands against the age based immunisation schedule.
 */
enum ImmunisationStatus: string
{
    use HasOptions;

    case Complete = 'complete';
    case NotYetDue = 'not_yet_due';
    case Due = 'due';
    case Overdue = 'overdue';
    case NoSchedule = 'no_schedule';

    public function label(): string
    {
        return match ($this) {
            self::Complete => 'Complete',
            self::NotYetDue => 'Not yet due',
            self::Due => 'Due now',
            self::Overdue => 'Overdue',
            self::NoSchedule => 'No schedule',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Complete => 'success',
            self::NotYetDue => 'neutral',
            self::Due => 'info',
            self::Overdue => 'danger',
            self::NoSchedule => 'neutral',
        };
    }

    /**
     * Severity order used when showing a schedule. Overdue first.
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::Overdue => 0,
            self::Due => 1,
            self::NotYetDue => 2,
            self::NoSchedule => 3,
            self::Complete => 4,
        };
    }
}
