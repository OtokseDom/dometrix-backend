<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        $managerId = DB::table('users')->first()?->id;

        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping WarehouseSeeder.');
            return;
        }

        $now = Carbon::now();

        $warehouses = [
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'WH_RAW_MAT',
                'name' => 'Raw Material Warehouse',
                'type' => 'raw_material',
                'location' => 'Building A, Block 1',
                'is_active' => true,
                'manager_user_id' => $managerId ? (string) $managerId : null,
                'metadata' => json_encode(['capacity' => '1000 pallets', 'climate_controlled' => false]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'WH_FINISHED',
                'name' => 'Finished Goods Warehouse',
                'type' => 'finished_goods',
                'location' => 'Building B, Block 2',
                'is_active' => true,
                'manager_user_id' => $managerId ? (string) $managerId : null,
                'metadata' => json_encode(['capacity' => '500 pallets', 'climate_controlled' => true]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'WH_WIP',
                'name' => 'Work in Progress Warehouse',
                'type' => 'wip',
                'location' => 'Building A, Block 2',
                'is_active' => true,
                'manager_user_id' => $managerId ? (string) $managerId : null,
                'metadata' => json_encode(['capacity' => '300 pallets', 'climate_controlled' => true]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('warehouses')->insert($warehouses);

        $this->command->info('✅ WarehouseSeeder completed: ' . count($warehouses) . ' warehouses created.');
    }
}
