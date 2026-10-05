<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff may only act on records that belong to their own facility.
 */
trait ChecksFacility
{
    protected function sameFacility(User $user, Model $record): bool
    {
        return $user->facility_id !== null && $user->facility_id === $record->facility_id;
    }
}
