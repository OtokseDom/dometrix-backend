<?php

namespace Tests\Feature\MasterData;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Units\Models\Units;
use App\Domain\Currencies\Models\Currencies;
use App\Domain\Categories\Models\Category;
use App\Domain\Taxes\Models\Tax;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitsTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing units
     */
    public function test_user_can_list_units(): void
    {
        $this->authenticateAs();
        Units::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/units');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Units retrieved']);
    }

    /**
     * Test creating a unit
     */
    public function test_user_can_create_unit(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/units', [
            'code' => 'KG',
            'name' => 'Kilogram',
            'type' => 'weight',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Unit created']);

        $this->assertDatabaseHas('units', ['code' => 'KG', 'name' => 'Kilogram']);
    }

    /**
     * Test viewing a unit
     */
    public function test_user_can_view_unit(): void
    {
        $this->authenticateAs();
        $unit = Units::factory()->create();

        $response = $this->getJson("/api/v1/units/{$unit->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['code' => $unit->code, 'name' => $unit->name],
            ]);
    }

    /**
     * Test updating a unit
     */
    public function test_user_can_update_unit(): void
    {
        $this->authenticateAs();
        $unit = Units::factory()->create();

        $response = $this->patchJson("/api/v1/units/{$unit->id}", [
            'name' => 'Updated Unit Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'Updated Unit Name']);
    }

    /**
     * Test deleting a unit
     */
    public function test_user_can_delete_unit(): void
    {
        $this->authenticateAs();
        $unit = Units::factory()->create();

        $response = $this->deleteJson("/api/v1/units/{$unit->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('units', ['id' => $unit->id]);
    }

    /**
     * Test unit creation fails with missing required fields
     */
    public function test_create_unit_fails_with_missing_code(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/units', [
            'name' => 'Kilogram',
            'type' => 'weight',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }
}
