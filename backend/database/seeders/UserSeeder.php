<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => "Superadmin User",
                'email' => "superadmin@demo.com",
                'password' => 'admin123',
            ],
            [
                'name' => "Admin User",
                'email' => "admin@demo.com",
                'password' => 'admin123',
            ],
            [
                'name' => "Employee User",
                'email' => "employee@demo.com",
                'password' => 'admin123',
            ],
        ];

        foreach ($users as $user) {
            User::create([
                'id' => (string) Str::uuid(),
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => now(),
                'password' => Hash::make($user['password']),
                'is_active' => true,
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
