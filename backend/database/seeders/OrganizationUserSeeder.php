<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use App\Domain\Role\Models\Role;

class OrganizationUserSeeder extends Seeder
{
    public function run(): void
    {
        // Get the default organization
        $organization = Organization::where('code', 'default')->first();

        if (!$organization) {
            $this->command->info('Default organization not found. Seeder skipped.');
            return;
        }

        // Map roles by name for easy access
        $roles = Role::all()->keyBy('name');

        // Map users by name
        $users = User::all()->keyBy('name');

        // Define assignments
        $assignments = [
            'Superadmin User' => 'Superadmin',
            'Admin User' => 'Admin',
            'Employee User' => 'Employee',
        ];

        foreach ($assignments as $userName => $roleName) {
            $user = $users->get($userName);
            $role = $roles->get($roleName);

            if (!$user || !$role) {
                $this->command->warn("Skipping assignment for {$userName} / {$roleName}");
                continue;
            }

            // Avoid duplicates
            $exists = DB::table('organization_user')
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$exists) {
                DB::table('organization_user')->insert([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('OrganizationUser pivot table seeded successfully.');
    }
}
