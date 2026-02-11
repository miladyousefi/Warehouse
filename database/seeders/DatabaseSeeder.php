<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
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

        Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name_tr' => 'Ana Depo',
                'name_en' => 'Main Warehouse',
                'address' => null,
                'sort_order' => 1,
            ]
        );

        Warehouse::firstOrCreate(
            ['code' => 'KITCHEN'],
            [
                'name_tr' => 'Mutfak Deposu',
                'name_en' => 'Kitchen Store',
                'address' => null,
                'sort_order' => 2,
            ]
        );

        // Run other seeders
        $this->call([
            RoleAndPermissionSeeder::class,
            // WarehouseInitialDataSeeder::class,
            RestaurantMaterialsSeeder::class,
            SuperAdminSeeder::class
        ]);

        // Assign admin role
        $user->assignRole('admin');
    }
}
