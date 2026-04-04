<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryBatchAndBalanceTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing inventory batches
     */
    public function test_user_can_list_batches(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        InventoryBatch::factory()->count(3)->for($organization)->create();

        $response = $this->getJson('/api/v1/inventory/batches');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test viewing inventory balance
     */
    public function test_user_can_view_inventory_balance(): void
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

        $balance = InventoryBalance::create([
            'organization_id' => $organization->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'batch_id' => $batch->id,
            'on_hand_qty' => 100,
            'reserved_qty' => 20,
            'available_qty' => 80,
            'average_cost' => 50.00,
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/inventory/balances/{$balance->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'on_hand_qty' => 100,
                    'reserved_qty' => 20,
                    'available_qty' => 80,
                ],
            ]);
    }

    /**
     * Test batch with expiry date handling
     */
    public function test_batch_with_expiry_date_tracked(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();

        $expiryDate = now()->addMonths(6)->toDateString();

        $batch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create([
                'batch_number' => 'BATCH-2026-001',
                'expiry_date' => $expiryDate,
            ]);

        $this->assertDatabaseHas('inventory_batches', [
            'batch_number' => 'BATCH-2026-001',
            'expiry_date' => $expiryDate,
        ]);
    }

    /**
     * Test expired batch status
     */
    public function test_expired_batch_status(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();

        $expiredBatch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->expired()
            ->create();

        $this->assertDatabaseHas('inventory_batches', [
            'id' => $expiredBatch->id,
            'status' => 'EXPIRED',
        ]);
    }

    /**
     * Test closed batch status
     */
    public function test_closed_batch_status(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();

        $closedBatch = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->closed()
            ->create();

        $this->assertDatabaseHas('inventory_batches', [
            'id' => $closedBatch->id,
            'status' => 'CLOSED',
            'remaining_qty' => 0,
        ]);
    }

    /**
     * Test inventory batch state transitions
     */
    public function test_batch_can_transition_from_active_to_expired(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $batch = InventoryBatch::factory()
            ->for($organization)
            ->state(['status' => 'ACTIVE'])
            ->create(['expiry_date' => now()->subDays(1)->toDateString()]);

        // Update status to EXPIRED
        $batch->update(['status' => 'EXPIRED']);

        $this->assertDatabaseHas('inventory_batches', [
            'id' => $batch->id,
            'status' => 'EXPIRED',
        ]);
    }

    /**
     * Test batch FIFO ordering for cost layers
     */
    public function test_batch_fifo_ordering_by_received_date(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();

        // Create batches with different received dates
        $batch1 = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create([
                'batch_number' => 'BATCH-001',
                'received_date' => now()->subDays(10)->toDateString(),
                'received_qty' => 50,
            ]);

        $batch2 = InventoryBatch::factory()
            ->for($organization)
            ->for($warehouse)
            ->for($material)
            ->create([
                'batch_number' => 'BATCH-002',
                'received_date' => now()->toDateString(),
                'received_qty' => 100,
            ]);

        // Get batches ordered by received_date
        $batches = InventoryBatch::where('material_id', $material->id)
            ->orderBy('received_date')
            ->get();

        $this->assertEquals('BATCH-001', $batches->first()->batch_number);
        $this->assertEquals('BATCH-002', $batches->last()->batch_number);
    }
}
