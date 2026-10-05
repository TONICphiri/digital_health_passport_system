<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\User;

/**
 * Account management. Facility Administrators manage health workers at their
 * own facility. The System Administrator manages Facility Administrators.
 */
class UserPolicy
{
    public function manage(User $user, User $account): bool
    {
        if ($user->id === $account->id) {
            return false;
        }

        if ($user->can(Permission::ManageFacilityAdministrators->value)) {
            return $account->isRole(RoleName::FacilityAdmin);
        }

        return $user->can(Permission::ManageStaff->value)
            && $user->worksAt($account->facility_id)
            && in_array($account->role(), RoleName::facilityStaffRoles(), true);
    }
}
