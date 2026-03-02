<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'restaurant_menu.view',
            'restaurant_menu.create',
            'restaurant_menu.edit',
            'restaurant_orders.view',
            'restaurant_orders.edit',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // Additive assignment only: do not revoke existing permissions.
        $rolePermissions = [
            'admin' => $permissions,
            'warehouse_manager' => [
                'restaurant_menu.view',
                'restaurant_menu.create',
                'restaurant_menu.edit',
                'restaurant_orders.view',
                'restaurant_orders.edit',
            ],
            'staff' => [
                'restaurant_menu.view',
                'restaurant_orders.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                continue;
            }

            foreach ($perms as $perm) {
                $role->givePermissionTo($perm);
            }
        }
    }
}
