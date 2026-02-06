<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@warehouse.local');
        $password = env('ADMIN_PASSWORD', 'Admin1234!');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => bcrypt($password),
            ]
        );

        // Assign admin role if permission system exists
        if (method_exists($user, 'assignRole')) {
            try {
                $user->assignRole('admin');
            } catch (\Throwable $e) {
                // ignore if roles not configured yet
            }
        }
    }
}
