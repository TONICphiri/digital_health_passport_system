<?php

namespace App\Policies;

use App\Enums\PatientStatus;
use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;
use App\Services\PassportAccessService;

/**
 * A passport is closed until the holder is present. A health worker can read
 * or add to it only while a scan (or the identity check that replaces it) is
 * open. The holder, and the mother of a child, can always read their own.
 */
class PatientPolicy
{
    public function __construct(private readonly PassportAccessService $access)
    {
    }

    public function open(User $user): bool
    {
        return $user->can(Permission::OpenPassports->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RegisterPatients->value);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->ownsPatientRecord($patient)
            || ($user->can(Permission::OpenPassports->value) && $this->access->hasOpen($user, $patient));
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can(Permission::EditPatientDemographics->value) && $this->access->hasOpen($user, $patient);
    }

    public function record(User $user, Patient $patient): bool
    {
        return $patient->status === PatientStatus::Active
            && $user->can(Permission::RecordEncounters->value)
            && $this->access->hasOpen($user, $patient);
    }

    public function recordVaccination(User $user, Patient $patient): bool
    {
        return $patient->status === PatientStatus::Active
            && $user->can(Permission::RecordVaccinations->value)
            && $this->access->hasOpen($user, $patient);
    }

    public function manageReminders(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ManageReminders->value) && $this->access->hasOpen($user, $patient);
    }

    public function printCard(User $user, Patient $patient): bool
    {
        return $user->ownsPatientRecord($patient)
            || ($user->can(Permission::RegisterPatients->value) && $this->access->hasOpen($user, $patient));
    }
}
