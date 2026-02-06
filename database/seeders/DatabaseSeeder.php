<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user first
        $user = User::firstOrCreate(
            ['email' => 'admin@thehunger.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );

        // Run other seeders
        $this->call([
            RoleAndPermissionSeeder::class,
            WarehouseInitialDataSeeder::class,
            RestaurantMaterialsSeeder::class,
            SuperAdminSeeder::class
        ]);

        // Assign admin role
        $user->assignRole('admin');
    }
}
