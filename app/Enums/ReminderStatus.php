<?php

namespace App\Enums;

/**
 * Whether a reminder is still being sent.
 */
enum ReminderStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Completed => 'neutral',
            self::Cancelled => 'neutral',
        };
    }
}
