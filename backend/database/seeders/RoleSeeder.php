<?php

namespace Database\Seeders;

use App\Domain\Role\Models\Role;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first organization
        $organization = Organization::first();

        if (!$organization) {
            $this->command->error("No organization found. Please seed an organization first.");
            return;
        }

        $roles = [
            ['name' => "Superadmin", 'permissions' => []],
            ['name' => "Admin", 'permissions' => []],
            ['name' => "Employee", 'permissions' => []],
        ];

        foreach ($roles as $role) {
            Role::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organization->id,  // Assign first org ID
                'name' => $role['name'],
                'permissions' => $role['permissions'],
            ]);
        }
    }
}
