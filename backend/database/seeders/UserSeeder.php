<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
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
                'organization_id' => 1,
                'role_id' => 1,
                'name' => "Superadmin User",
                'email' => "superadmin@demo.com", //dom@gmail.com
                'email_verified_at' => now(),
                'password' => 'admin123', // 1
                'is_active' => true,
                'remember_token' => Str::random(10),
            ],
            [
                'organization_id' => 1,
                'role_id' => 2,
                'name' => "Admin User",
                'email' => "admin@demo.com", //dom@gmail.com
                'email_verified_at' => now(),
                'password' => 'admin123', // 1
                'is_active' => true,
                'remember_token' => Str::random(10),
            ],
            [
                'organization_id' => 1,
                'role_id' => 3,
                'name' => "Employee User",
                'email' => "employee@demo.com", //dom@gmail.com
                'email_verified_at' => now(),
                'password' => 'admin123', // 1
                'is_active' => true,
                'remember_token' => Str::random(10),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
