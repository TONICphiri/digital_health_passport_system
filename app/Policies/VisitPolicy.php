<?php

namespace App\Policies;

use App\Enums\PatientStatus;
use App\Enums\Permission;
use App\Models\User;
use App\Models\Visit;
use App\Services\PassportAccessService;

/**
 * Decides who may record each stage of a visit. A worker needs the visits
 * permission, the position that owns the stage, an open passport, and a visit
 * that is not yet locked. This is checked on the server for every save, so a
 * stage cannot be recorded by posting the form directly.
 */
class VisitPolicy
{
    public function __construct(private readonly PassportAccessService $access)
    {
    }

    public function recordStage(User $user, Visit $visit, string $type): bool
    {
        return $this->allowed($user, $visit, Permission::ManageVisits, $this->positionsFor($type));
    }

    public function dispense(User $user, Visit $visit): bool
    {
        return $this->allowed($user, $visit, Permission::DispenseMedications, $this->positionsFor('dispensing'));
    }

    /**
     * @param  array<int, string>  $positions
     */
    private function allowed(User $user, Visit $visit, Permission $permission, array $positions): bool
    {
        if (! $user->can($permission->value)) {
            return false;
        }

        if ($visit->isLocked()) {
            return false;
        }

        if (! $user->hasPosition($positions)) {
            return false;
        }

        $patient = $visit->patient;

        return $patient !== null
            && $patient->status === PatientStatus::Active
            && $this->access->hasOpen($user, $patient);
    }

    /**
     * @return array<int, string>
     */
    private function positionsFor(string $type): array
    {
        return (array) config("health_passport.stage_positions.{$type}", []);
    }
}
