<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Writes the roles and permissions defined in the RoleName enum to the
 * database. Running it again brings the database in line with the code.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleName::cases() as $roleName) {
            Role::findOrCreate($roleName->value, 'web')
                ->syncPermissions(array_map(fn (PermissionName $permission) => $permission->value, $roleName->permissions()));
        }

        // Roles and permissions that no longer exist in the code are removed.
        Role::query()->whereNotIn('name', RoleName::values())->delete();
        Permission::query()->whereNotIn('name', PermissionName::values())->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
