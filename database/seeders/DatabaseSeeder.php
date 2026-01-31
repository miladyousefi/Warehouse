<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            WarehouseInitialDataSeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@thehunger.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
        $user->assignRole('admin');
    }
}
