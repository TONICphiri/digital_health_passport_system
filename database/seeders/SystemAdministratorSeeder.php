<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first System Administrator from the values in the environment
 * file. The password must be changed at first login.
 */
class SystemAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => config('health_passport.admin.email')],
            [
                'name' => config('health_passport.admin.name'),
                'job_title' => 'System Administrator',
                'status' => UserStatus::Active,
                'must_change_password' => ! config('health_passport.seed_demo_data'),
                'password' => config('health_passport.admin.password'),
            ],
        );

        $admin->syncRoles([RoleName::SystemAdmin->value]);
    }
}
