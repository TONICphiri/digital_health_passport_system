<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Creates the first System Administrator account from the values in
 * the environment file. The password must be changed at first login,
 * so a live server can be seeded without a password in the environment:
 * a one-time password is shown once here and the account is locked
 * behind a forced password change. To set the password directly, run
 * `php artisan admin:create` instead.
 */
class SystemAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) config('health_passport.admin.password');

        if ($password === '') {
            $password = Str::password(24);

            $this->command?->warn("ADMIN_PASSWORD is not set. One-time administrator password for {$this->email()}: {$password}");

            Log::warning('A one-time administrator password was generated during seeding because ADMIN_PASSWORD is not set.');
        }

        $admin = User::query()->firstOrCreate(
            ['email' => $this->email()],
            [
                'name' => config('health_passport.admin.name', 'System Administrator'),
                'job_title' => 'System Administrator',
                'status' => UserStatus::Active,
                'must_change_password' => ! config('health_passport.seed_demo_data'),
                'password' => $password,
            ],
        );

        $admin->syncRoles([RoleName::SystemAdmin->value]);
    }

    private function email(): string
    {
        return (string) config('health_passport.admin.email', 'admin@healthpassport.mw');
    }
}
