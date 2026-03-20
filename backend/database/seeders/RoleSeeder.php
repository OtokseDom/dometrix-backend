<?php

namespace Database\Seeders;

use App\Domain\Role\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => "Superadmin", 'permissions' => []],
            ['name' => "Admin", 'permissions' => []],
            ['name' => "Employee", 'permissions' => []],
        ];

        foreach ($roles as $role) {
            Role::create([
                'id' => (string) Str::uuid(),
                'name' => $role['name'],
                'permissions' => $role['permissions'],
            ]);
        }
    }
}
