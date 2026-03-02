<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RestaurantOrderTakePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'restaurant_orders.take_order',
            'guard_name' => 'web',
        ]);

        $admin = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin && !$admin->hasPermissionTo($permission)) {
            $admin->givePermissionTo($permission);
        }
    }
}
