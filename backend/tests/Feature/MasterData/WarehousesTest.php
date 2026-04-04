<?php

namespace Tests\Feature\MasterData;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\User\Models\User;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehousesTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing warehouses
     */
    public function test_user_can_list_warehouses(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        Warehouse::factory()->count(3)->for($organization)->create();

        $response = $this->getJson('/api/v1/warehouses');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Warehouses retrieved']);
    }

    /**
     * Test creating a warehouse
     */
    public function test_user_can_create_warehouse(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->postJson('/api/v1/warehouses', [
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'type' => 'main',
            'location' => '123 Main St',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Warehouse created']);

        $this->assertDatabaseHas('warehouses', [
            'organization_id' => $organization->id,
            'code' => 'WH-001',
        ]);
    }

    /**
     * Test warehouse created as inactive works
     */
    public function test_warehouse_can_be_inactive(): void
    {
        $user = $this->authenticateAs();

        $this->postJson('/api/v1/warehouses', [
            'code' => 'WH-INACTIVE',
            'name' => 'Inactive Warehouse',
            'type' => 'secondary',
            'location' => 'Some location',
            'is_active' => false,
        ])->assertStatus(201);

        $this->assertDatabaseHas('warehouses', [
            'code' => 'WH-INACTIVE',
            'is_active' => false,
        ]);
    }

    /**
     * Test warehouse with manager assignment
     */
    public function test_warehouse_can_have_manager(): void
    {
        $user = $this->authenticateAs();
        $manager = User::factory()->create();

        $response = $this->postJson('/api/v1/warehouses', [
            'code' => 'WH-MANAGER',
            'name' => 'Managed Warehouse',
            'type' => 'main',
            'location' => 'Some location',
            'manager_user_id' => $manager->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('warehouses', [
            'code' => 'WH-MANAGER',
            'manager_user_id' => $manager->id,
        ]);
    }

    /**
     * Test viewing a warehouse
     */
    public function test_user_can_view_warehouse(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['code' => $warehouse->code]]);
    }

    /**
     * Test updating a warehouse
     */
    public function test_user_can_update_warehouse(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/warehouses/{$warehouse->id}", [
            'name' => 'Updated Warehouse',
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Updated Warehouse',
            'is_active' => false,
        ]);
    }

    /**
     * Test deleting a warehouse
     */
    public function test_user_can_delete_warehouse(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $warehouse = Warehouse::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
    }

    /**
     * Test warehouse types
     */
    public function test_warehouse_types_stored_correctly(): void
    {
        $user = $this->authenticateAs();

        $types = ['main', 'secondary', 'distribution', 'overflow'];

        foreach ($types as $type) {
            $this->postJson('/api/v1/warehouses', [
                'code' => "WH-{$type}",
                'name' => "Warehouse {$type}",
                'type' => $type,
                'location' => 'Location',
            ])->assertStatus(201);

            $this->assertDatabaseHas('warehouses', ['type' => $type]);
        }
    }

    /**
     * Test warehouse creation fails with missing required fields
     */
    public function test_create_warehouse_fails_with_missing_code(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/warehouses', [
            'name' => 'Missing Code',
            'type' => 'main',
            'location' => 'Location',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }
}
