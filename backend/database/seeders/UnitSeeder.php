<?php

namespace Database\Seeders;

use App\Domain\Units\Models\Units;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping UnitSeeder.');
            return;
        }
        Units::insert([
            // ===== SYSTEM MASTER UNITS =====
            // Quantity

            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'pcs',
                'name' => 'piece',
                'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'dozen',
                'name' => 'dozen',
                'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'box',
                'name' => 'box',
                'type' => 'quantity',
                'metadata' => null
            ],

            // Weight
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'kg',
                'name' => 'kilogram',
                'type' => 'weight',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'g',
                'name' => 'gram',
                'type' => 'weight',
                'metadata' => null
            ],

            // Volume
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'l',
                'name' => 'liter',
                'type' => 'volume',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'ml',
                'name' => 'milliliter',
                'type' => 'volume',
                'metadata' => null
            ],

            // Length
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'm',
                'name' => 'meter',
                'type' => 'length',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'cm',
                'name' => 'centimeter',
                'type' => 'length',
                'metadata' => null
            ],

            // ===== BUSINESS / OPTIONAL UNITS =====
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'crate',
                'name' => 'crate',
                'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'bundle',
                'name' => 'bundle',
                'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'pack',
                'name' => 'pack',
                'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'set',
                'name' => 'set',
                'type' => 'quantity',
                'metadata' => null
            ],
        ]);
    }
}
