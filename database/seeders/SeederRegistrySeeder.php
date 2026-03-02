<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SeederRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Must run first so roles exist before any role assignment.
            // RoleAndPermissionSeeder::class,
            MenuPermissionsSeeder::class,
            RestaurantOrderTakePermissionSeeder::class,
            RestaurantTableSeeder::class,
            // RestaurantMaterialsSeeder::class,
            RestaurantMenuSeeder::class,
            // SuperAdminSeeder::class,
        ]);
    }
}
