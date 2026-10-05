<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data is always loaded. Demonstration accounts and patients
     * are only loaded when SEED_DEMO_DATA is true in the environment file,
     * so a live server can be set up without sample records.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            DistrictSeeder::class,
            VaccineSeeder::class,
            SystemAdministratorSeeder::class,
        ]);

        if (config('health_passport.seed_demo_data')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
