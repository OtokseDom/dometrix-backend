<?php

namespace Database\Seeders;

use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Inventory\Models\InventoryCostLayer;
use App\Domain\Units\Models\Units;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * InventorySeeder
 * 
 * Seeds sample inventory data for testing and demonstration.
 * Creates realistic purchase receipts, production movements, and batch tracking.
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $org = DB::table('organizations')->first();
        if (!$org) {
            $this->command->warn('No organization found. Run OrganizationSeeder first.');
            return;
        }

        $orgId = $org->id;

        DB::transaction(function () use ($orgId) {
            $this->seedWarehouses($orgId);
            $this->seedInventory($orgId);
            $this->seedBatches($orgId);
            $this->seedMovements($orgId);
        });

        $this->command->info('✅ Inventory seeding completed successfully!');
    }

    private function seedWarehouses(string $orgId): void
    {
        $existingCount = Warehouse::where('organization_id', $orgId)->count();
        if ($existingCount > 0) {
            $this->command->line('⏭ Warehouses already exist. Skipping.');
            return;
        }

        $warehouses = [
            [
                'code' => 'WH_RM',
                'name' => 'Raw Materials',
                'type' => 'raw_material',
                'location' => 'Building A, Floor 1',
            ],
            [
                'code' => 'WH_FG',
                'name' => 'Finished Goods',
                'type' => 'finished_goods',
                'location' => 'Building B, Floor 2',
            ],
            [
                'code' => 'WH_WIP',
                'name' => 'Work in Progress',
                'type' => 'wip',
                'location' => 'Production Floor',
            ],
            [
                'code' => 'WH_TRANSIT',
                'name' => 'Transit',
                'type' => 'transit',
                'location' => 'Logistics Center',
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $orgId,
                'code' => $wh['code'],
                'name' => $wh['name'],
                'type' => $wh['type'],
                'location' => $wh['location'],
                'is_active' => true,
                'metadata' => ['created_by' => 'seeder'],
            ]);
        }

        $this->command->line('✓ Created 4 warehouses');
    }

    private function seedInventory(string $orgId): void
    {
        // Get or create materials for demo
        $materials = Material::where('organization_id', $orgId)->limit(5)->get();

        if ($materials->isEmpty()) {
            $this->command->warn('No materials found. Create materials first.');
            return;
        }

        $warehouses = Warehouse::where('organization_id', $orgId)->get();

        if ($warehouses->isEmpty()) {
            $this->command->warn('No warehouses found.');
            return;
        }

        $count = 0;
        foreach ($materials as $material) {
            foreach ($warehouses->take(2) as $warehouse) {
                $qty = rand(500, 5000);
                $unitCost = rand(10, 500) + rand(0, 99) / 100;

                InventoryBalance::updateOrCreate(
                    [
                        'organization_id' => $orgId,
                        'warehouse_id' => $warehouse->id,
                        'material_id' => $material->id,
                        'batch_id' => null,
                    ],
                    [
                        'on_hand_qty' => $qty,
                        'reserved_qty' => rand(0, 100),
                        'available_qty' => $qty - rand(0, 100),
                        'average_cost' => $unitCost,
                        'updated_at' => now(),
                    ]
                );
                $count++;
            }
        }

        $this->command->line("✓ Created {$count} inventory balances");
    }

    private function seedBatches(string $orgId): void
    {
        $materials = Material::where('organization_id', $orgId)->limit(3)->get();
        $warehouses = Warehouse::where('organization_id', $orgId)->get();

        if ($materials->isEmpty() || $warehouses->isEmpty()) {
            return;
        }

        $count = 0;
        $now = now();

        foreach ($materials as $material) {
            foreach ($warehouses->take(1) as $warehouse) {
                for ($i = 0; $i < 3; $i++) {
                    $receivedDate = $now->copy()->subDays(30)->addDays($i * 10);
                    $expiryDate = $receivedDate->copy()->addMonths(12);

                    InventoryBatch::create([
                        'id' => (string) Str::uuid(),
                        'organization_id' => $orgId,
                        'material_id' => $material->id,
                        'warehouse_id' => $warehouse->id,
                        'batch_number' => 'BATCH-' . $material->code . '-' . strtoupper(Str::random(4)),
                        'manufactured_date' => $receivedDate->copy()->subDays(5),
                        'received_date' => $receivedDate,
                        'expiry_date' => $expiryDate,
                        'received_qty' => rand(1000, 5000),
                        'remaining_qty' => rand(500, 4000),
                        'unit_cost' => rand(10, 500) + rand(0, 99) / 100,
                        'status' => 'ACTIVE',
                        'metadata' => [
                            'supplier_id' => 'SUP-' . Str::random(5),
                            'certificate_available' => true,
                        ],
                    ]);
                    $count++;
                }
            }
        }

        $this->command->line("✓ Created {$count} inventory batches");
    }

    private function seedMovements(string $orgId): void
    {
        $materials = Material::where('organization_id', $orgId)->limit(2)->get();
        $warehouses = Warehouse::where('organization_id', $orgId)->get();
        $units = Units::where('organization_id', $orgId)->first();

        if ($materials->isEmpty() || $warehouses->isEmpty() || !$units) {
            return;
        }

        $count = 0;
        $now = now();

        // Create sample purchase receipts
        foreach ($materials->take(1) as $material) {
            $warehouse = $warehouses->first();

            for ($i = 0; $i < 5; $i++) {
                $qty = rand(100, 500);
                $unitCost = rand(10, 100) + rand(0, 99) / 100;
                $totalCost = $qty * $unitCost;
                $createdAt = $now->copy()->subDays(20)->addDays($i * 4);

                InventoryMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $orgId,
                    'warehouse_id' => $warehouse->id,
                    'material_id' => $material->id,
                    'batch_id' => null,
                    'reference_type' => 'Purchase Order',
                    'reference_id' => 'PO-2025-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'movement_type' => 'PURCHASE_RECEIPT',
                    'quantity' => $qty,
                    'unit_of_measure_id' => $units->id,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'running_balance' => rand(1000, 5000),
                    'direction' => 'IN',
                    'performed_by' => null,
                    'remarks' => "Purchase receipt from supplier #$i",
                    'metadata' => [
                        'invoice' => 'INV-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                        'supplier_reference' => 'SR-' . Str::random(6),
                    ],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $count++;
            }
        }

        // Create sample consumption movements
        foreach ($materials->take(1) as $material) {
            $warehouse = $warehouses->where('type', 'raw_material')->first() ?? $warehouses->first();

            for ($i = 0; $i < 3; $i++) {
                $qty = rand(50, 200);
                $unitCost = rand(10, 100) + rand(0, 99) / 100;
                $totalCost = $qty * $unitCost;
                $createdAt = $now->copy()->subDays(10)->addDays($i * 3);

                InventoryMovement::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $orgId,
                    'warehouse_id' => $warehouse->id,
                    'material_id' => $material->id,
                    'batch_id' => null,
                    'reference_type' => 'Work Order',
                    'reference_id' => 'WO-2025-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                    'movement_type' => 'PRODUCTION_CONSUMPTION',
                    'quantity' => $qty,
                    'unit_of_measure_id' => $units->id,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'running_balance' => rand(500, 3000),
                    'direction' => 'OUT',
                    'performed_by' => null,
                    'remarks' => "Consumed for production WO-2025-" . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                    'metadata' => [
                        'production_line' => 'LINE-' . ($i % 3 + 1),
                        'batch_consumed' => 'BATCH-' . Str::random(8),
                    ],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $count++;
            }
        }

        $this->command->line("✓ Created {$count} inventory movements");
    }
}
