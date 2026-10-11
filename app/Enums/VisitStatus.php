<?php

namespace App\Enums;

/**
 * Where a staged visit has reached. A completed visit is locked.
 */
enum VisitStatus: string
{
    use HasOptions;

    case InProgress = 'in_progress';
    case AwaitingDispense = 'awaiting_dispense';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::AwaitingDispense => 'Waiting for medicines',
            self::Completed => 'Completed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InProgress => 'info',
            self::AwaitingDispense => 'warning',
            self::Completed => 'success',
        };
    }
}
