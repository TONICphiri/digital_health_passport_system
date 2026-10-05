<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and updates staff accounts. New accounts receive a one time
 * password that must be changed at first sign in.
 */
class StaffAccountService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, temporary_password: string}
     */
    public function create(array $data, RoleName $role): array
    {
        $temporaryPassword = Str::password(10, symbols: false);

        $user = DB::transaction(function () use ($data, $role, $temporaryPassword) {
            $user = User::create([
                ...$data,
                'status' => UserStatus::Active,
                'must_change_password' => true,
                'password' => $temporaryPassword,
            ]);

            $user->assignRole($role->value);

            return $user;
        });

        $this->audit->record('user.created', "Created the {$role->label()} account for {$user->name}.", $user);

        return ['user' => $user, 'temporary_password' => $temporaryPassword];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, ?RoleName $role = null): void
    {
        DB::transaction(function () use ($user, $data, $role) {
            $user->update($data);

            if ($role && $user->role() !== $role) {
                $user->syncRoles([$role->value]);
            }
        });

        $this->audit->record('user.updated', "Updated the account for {$user->name}.", $user);
    }

    public function toggleStatus(User $user): void
    {
        $user->update([
            'status' => $user->isActive() ? UserStatus::Inactive : UserStatus::Active,
        ]);

        $state = $user->isActive() ? 'activated' : 'deactivated';
        $this->audit->record('user.status-changed', "The account for {$user->name} was {$state}.", $user);
    }

    public function resetPassword(User $user): string
    {
        $temporaryPassword = Str::password(10, symbols: false);

        $user->update(['password' => $temporaryPassword, 'must_change_password' => true]);
        $this->audit->record('user.password-reset', "Reset the password for {$user->name}.", $user);

        return $temporaryPassword;
    }
}
