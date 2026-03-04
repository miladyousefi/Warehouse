<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RestaurantRealtimePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'restaurant_orders.calls.handle',
            'restaurant_orders.monitor.confirm_cancel',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'admin' => $permissions,
            'warehouse_manager' => $permissions,
            'staff' => [
                'restaurant_orders.monitor.confirm_cancel',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                continue;
            }

            foreach ($perms as $perm) {
                if (!$role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
        }
    }
}
