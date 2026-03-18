<?php

namespace Database\Seeders;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::create([
            'name' => "Default",
            'code' => "default",
            'timezone' => "UTC",
            'currency' => "USD",
        ]);
    }
}
