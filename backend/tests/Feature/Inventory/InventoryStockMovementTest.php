<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use Tests\Traits\AuditTrailTestHelper;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Units\Models\Units;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryStockMovementTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;
    use AuditTrailTestHelper;

    /**
     * Test stock in movement
     */
    public function test_user_can_record_stock_in_movement(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();
        $batch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create(['received_qty' => 0, 'remaining_qty' => 0]);
        $unit = Units::factory()->create();

        // Create initial balance at 0
        InventoryBalance::create([
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'on_hand_qty' => 0,
            'reserved_qty' => 0,
            'available_qty' => 0,
            'average_cost' => 0,
        ]);

        // Record stock in
        $response = $this->postJson('/api/v1/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'movement_type' => 'STOCK_IN',
            'quantity' => 100,
            'unit_of_measure_id' => $unit->id,
            'unit_cost' => 50.00,
            'reference_type' => 'PURCHASE_ORDER',
            'reference_id' => 'PO-001',
            'remarks' => 'Initial stock receipt',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Verify movement was recorded
        $this->assertDatabaseHas('inventory_movements', [
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'quantity' => 100,
            'direction' => 'IN',
        ]);

        // Verify balance was updated
        $balance = InventoryBalance::where('material_id', $material->id)->first();
        $this->assertEquals(100, $balance->on_hand_qty);
    }

    /**
     * Test stock out movement
     */
    public function test_user_can_record_stock_out_movement(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();
        $batch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create(['received_qty' => 100, 'remaining_qty' => 100]);
        $unit = Units::factory()->create();

        // Create balance with stock
        InventoryBalance::create([
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'on_hand_qty' => 100,
            'reserved_qty' => 0,
            'available_qty' => 100,
            'average_cost' => 50.00,
        ]);

        // Record stock out
        $response = $this->postJson('/api/v1/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'movement_type' => 'STOCK_OUT',
            'quantity' => 30,
            'unit_of_measure_id' => $unit->id,
            'unit_cost' => 50.00,
            'reference_type' => 'SALES_ORDER',
            'reference_id' => 'SO-001',
            'remarks' => 'Sale release',
        ]);

        $response->assertStatus(201);

        // Verify balance decreased
        $balance = InventoryBalance::where('material_id', $material->id)->first();
        $this->assertEquals(70, $balance->on_hand_qty);
    }

    /**
     * Test stock out fails when insufficient inventory
     */
    public function test_stock_out_fails_with_insufficient_inventory(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();
        $batch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create(['received_qty' => 20, 'remaining_qty' => 20]);
        $unit = Units::factory()->create();

        InventoryBalance::create([
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'on_hand_qty' => 20,
            'reserved_qty' => 0,
            'available_qty' => 20,
            'average_cost' => 50.00,
        ]);

        $response = $this->postJson('/api/v1/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'movement_type' => 'STOCK_OUT',
            'quantity' => 50,
            'unit_of_measure_id' => $unit->id,
            'reference_type' => 'SALES_ORDER',
            'reference_id' => 'SO-002',
        ]);

        $response->assertStatus(400);
    }

    /**
     * Test inventory balance tracking
     */
    public function test_inventory_balance_maintains_correct_values(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();
        $batch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create(['received_qty' => 0, 'remaining_qty' => 0]);
        $unit = Units::factory()->create();

        $balance = InventoryBalance::create([
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'on_hand_qty' => 0,
            'reserved_qty' => 0,
            'available_qty' => 0,
            'average_cost' => 0,
        ]);

        // Sequence: IN 100 -> OUT 30 -> IN 50 -> OUT 20
        $quantities = [
            ['type' => 'STOCK_IN', 'qty' => 100, 'expected' => 100],
            ['type' => 'STOCK_OUT', 'qty' => 30, 'expected' => 70],
            ['type' => 'STOCK_IN', 'qty' => 50, 'expected' => 120],
            ['type' => 'STOCK_OUT', 'qty' => 20, 'expected' => 100],
        ];

        foreach ($quantities as $movement) {
            $this->postJson('/api/v1/inventory/movements', [
                'warehouse_id' => $warehouse->id,
                'material_id' => $material->id,
                'batch_id' => $batch->id,
                'movement_type' => $movement['type'],
                'quantity' => $movement['qty'],
                'unit_of_measure_id' => $unit->id,
                'unit_cost' => 50.00,
                'reference_type' => 'TEST',
                'reference_id' => 'TEST-' . $movement['type'],
            ]);

            $balance->refresh();
            $this->assertEquals($movement['expected'], $balance->on_hand_qty);
        }
    }

    /**
     * Test adjustment movements
     */
    public function test_adjustment_movements_work(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();
        $batch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create();
        $unit = Units::factory()->create();

        InventoryBalance::create([
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'on_hand_qty' => 100,
            'reserved_qty' => 0,
            'available_qty' => 100,
            'average_cost' => 50.00,
        ]);

        // Record adjustment
        $response = $this->postJson('/api/v1/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'movement_type' => 'ADJUSTMENT_OUT',
            'quantity' => 5,
            'unit_of_measure_id' => $unit->id,
            'reference_type' => 'PHYSICAL_COUNT',
            'reference_id' => 'COUNT-2026-04',
            'remarks' => 'Damage adjustment',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'ADJUSTMENT_OUT',
        ]);
    }

    /**
     * Test multi-tenant isolation for inventory
     */
    public function test_user_cannot_access_other_org_inventory(): void
    {
        $this->authenticateAs();
        $otherOrg = Organization::factory()->create();

        $otherWarehouse = Warehouse::factory()->for($otherOrg)->create();
        $otherMaterial = Material::factory()->for($otherOrg)->create();

        $response = $this->postJson('/api/v1/inventory/movements', [
            'warehouse_id' => $otherWarehouse->id,
            'material_id' => $otherMaterial->id,
            'movement_type' => 'STOCK_IN',
            'quantity' => 100,
        ]);

        $this->assertThat(
            $response->status(),
            $this->logicalOr($this->equalTo(403), $this->equalTo(404), $this->equalTo(422))
        );
    }
}
