<?php

namespace App\Enums;

/**
 * The four stages a visit passes through, each recorded by one position.
 */
enum VisitStageType: string
{
    use HasOptions;

    case Reception = 'reception';
    case Triage = 'triage';
    case Consultation = 'consultation';
    case Dispensing = 'dispensing';

    public function label(): string
    {
        return match ($this) {
            self::Reception => 'Reception',
            self::Triage => 'Triage',
            self::Consultation => 'Consultation',
            self::Dispensing => 'Dispensing',
        };
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
