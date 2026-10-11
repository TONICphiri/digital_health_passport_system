<?php

namespace App\Services;

use App\Enums\ImmunisationStatus;
use App\Models\Vaccine;
use Illuminate\Support\Carbon;

/**
 * The place of one vaccine on a patient's age based immunisation schedule.
 */
final readonly class ImmunisationResult
{
    public function __construct(
        public Vaccine $vaccine,
        public ImmunisationStatus $status,
        public ?Carbon $dueOn = null,
        public ?Carbon $graceEndsOn = null,
    ) {}
}
