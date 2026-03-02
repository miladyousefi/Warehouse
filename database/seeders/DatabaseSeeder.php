<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

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

        // Run all project seeders in a controlled order.
        $this->call([
            SeederRegistrySeeder::class,
        ]);

        // Assign admin role (ensure it exists even on partial seed runs)
        if (method_exists($user, 'assignRole')) {
            if (!Role::query()->where('name', 'admin')->where('guard_name', 'web')->exists()) {
                $this->call([
                    RoleAndPermissionSeeder::class,
                    MenuPermissionsSeeder::class,
                ]);
            }

            $user->assignRole('admin');
        }
    }
}
