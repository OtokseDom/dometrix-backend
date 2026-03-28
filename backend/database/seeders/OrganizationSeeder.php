<?php

namespace Database\Seeders;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::create([
            'id' => (string) Str::uuid(),
            'name' => "Default",
            'code' => "default",
            'metadata' => [],
        ]);
    }
}
