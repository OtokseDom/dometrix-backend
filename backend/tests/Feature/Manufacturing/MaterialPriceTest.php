<?php

namespace Tests\Feature\Manufacturing;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Manufacturing\Models\MaterialPrice;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaterialPriceTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing material prices
     */
    public function test_user_can_list_material_prices(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();
        MaterialPrice::factory()->count(3)->for($material)->for($organization)->create();

        $response = $this->getJson('/api/v1/manufacturing/material-prices');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test creating a material price
     */
    public function test_user_can_create_material_price(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        $response = $this->postJson('/api/v1/manufacturing/material-prices', [
            'material_id' => $material->id,
            'price' => 100.50,
            'effective_date' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Material price created',
                'data' => [
                    'price' => 100.50,
                ],
            ]);

        $this->assertDatabaseHas('material_prices', [
            'material_id' => $material->id,
            'price' => 100.50,
        ]);
    }

    /**
     * Test material price with future effective date
     */
    public function test_material_price_can_have_future_effective_date(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();
        $futureDate = now()->addDays(30)->toDateString();

        $response = $this->postJson('/api/v1/manufacturing/material-prices', [
            'material_id' => $material->id,
            'price' => 150.00,
            'effective_date' => $futureDate,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('material_prices', [
            'material_id' => $material->id,
            'effective_date' => $futureDate,
        ]);
    }

    /**
     * Test viewing a material price
     */
    public function test_user_can_view_material_price(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();
        $price = MaterialPrice::factory()->for($material)->for($organization)->create();

        $response = $this->getJson("/api/v1/manufacturing/material-prices/{$price->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['price' => (float)$price->price]]);
    }

    /**
     * Test updating a material price
     */
    public function test_user_can_update_material_price(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();
        $price = MaterialPrice::factory()->for($material)->for($organization)->create();

        $response = $this->patchJson("/api/v1/manufacturing/material-prices/{$price->id}", [
            'price' => 200.75,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('material_prices', [
            'id' => $price->id,
            'price' => 200.75,
        ]);
    }

    /**
     * Test deleting a material price
     */
    public function test_user_can_delete_material_price(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();
        $price = MaterialPrice::factory()->for($material)->for($organization)->create();

        $response = $this->deleteJson("/api/v1/manufacturing/material-prices/{$price->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('material_prices', ['id' => $price->id]);
    }

    /**
     * Test price history endpoint
     */
    public function test_user_can_get_material_price_history(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        // Create prices for different dates
        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->pastDate()
            ->create(['price' => 50.00]);

        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->create(['price' => 75.00]);

        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->futureDate()
            ->create(['price' => 100.00]);

        $response = $this->getJson("/api/v1/manufacturing/materials/{$material->id}/price-history");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }
}
