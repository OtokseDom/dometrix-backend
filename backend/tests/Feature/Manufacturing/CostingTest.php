<?php

namespace Tests\Feature\Manufacturing;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Manufacturing\Models\MaterialPrice;
use App\Domain\Manufacturing\Models\Product;
use App\Domain\Manufacturing\Models\Bom;
use App\Domain\Manufacturing\Models\BomItem;
use App\Domain\Units\Models\Units;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CostingTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test calculating material cost
     */
    public function test_user_can_calculate_material_cost(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        // Create price for the material
        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->create(['price' => 100.00, 'effective_date' => now()->toDateString()]);

        $response = $this->postJson('/api/v1/manufacturing/material-cost', [
            'material_id' => $material->id,
            'quantity' => 10,
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_cost', 'unit_cost', 'quantity']]);
    }

    /**
     * Test material cost uses correct effective date price
     */
    public function test_material_cost_uses_effective_date_price(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $material = Material::factory()->for($organization)->create();

        // Create multiple prices for different dates
        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->pastDate()
            ->create(['price' => 50.00]);

        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->create(['price' => 100.00]);

        $response = $this->postJson('/api/v1/manufacturing/material-cost', [
            'material_id' => $material->id,
            'quantity' => 5,
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        // Should use current date's price (100.00)
        $this->assertEquals(500.00, $response->json('data.total_cost'));
    }

    /**
     * Test calculating BOM cost
     */
    public function test_user_can_calculate_bom_cost(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();
        $bom = Bom::factory()->for($organization)->for($product)->create();

        // Create BOM items with materials
        $unit = Units::factory()->create();
        for ($i = 0; $i < 3; $i++) {
            $material = Material::factory()->for($organization)->create();
            MaterialPrice::factory()
                ->for($material)
                ->for($organization)
                ->create(['price' => 100.00]);

            BomItem::factory()
                ->for($organization)
                ->for($bom)
                ->state([
                    'material_id' => $material->id,
                    'unit_id' => $unit->id,
                    'quantity' => 2,
                ])
                ->create();
        }

        $response = $this->postJson('/api/v1/manufacturing/bom-cost', [
            'bom_id' => $bom->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_cost', 'items_cost']]);
    }

    /**
     * Test BOM cost includes wastage
     */
    public function test_bom_cost_includes_wastage_percentage(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();
        $bom = Bom::factory()->for($organization)->for($product)->create();
        $unit = Units::factory()->create();

        $material = Material::factory()->for($organization)->create();
        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->create(['price' => 100.00]);

        BomItem::factory()
            ->for($organization)
            ->for($bom)
            ->state([
                'material_id' => $material->id,
                'unit_id' => $unit->id,
                'quantity' => 10,
                'wastage_percent' => 5, // 5% wastage
            ])
            ->create();

        $response = $this->postJson('/api/v1/manufacturing/bom-cost', [
            'bom_id' => $bom->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(200);
        // With 5% wastage: 10 * 1.05 * 100 = 1050
        $this->assertEquals(1050.00, $response->json('data.total_cost'));
    }

    /**
     * Test calculating product cost
     */
    public function test_user_can_calculate_product_cost(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();
        $bom = Bom::factory()
            ->for($organization)
            ->for($product)
            ->active()
            ->create();

        $unit = Units::factory()->create();
        $material = Material::factory()->for($organization)->create();
        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->create(['price' => 100.00]);

        BomItem::factory()
            ->for($organization)
            ->for($bom)
            ->state([
                'material_id' => $material->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
            ])
            ->create();

        $response = $this->postJson('/api/v1/manufacturing/product-cost', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_cost', 'bom_id', 'quantity']]);
    }

    /**
     * Test product cost uses active BOM
     */
    public function test_product_cost_uses_active_bom(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();
        $unit = Units::factory()->create();
        $material = Material::factory()->for($organization)->create();

        MaterialPrice::factory()
            ->for($material)
            ->for($organization)
            ->create(['price' => 100.00]);

        // Create inactive BOM
        $inactiveBom = Bom::factory()
            ->for($organization)
            ->for($product)
            ->state(['is_active' => false])
            ->create();

        BomItem::factory()
            ->for($organization)
            ->for($inactiveBom)
            ->state([
                'material_id' => $material->id,
                'unit_id' => $unit->id,
                'quantity' => 10,
            ])
            ->create();

        // Create active BOM
        $activeBom = Bom::factory()
            ->for($organization)
            ->for($product)
            ->active()
            ->create();

        BomItem::factory()
            ->for($organization)
            ->for($activeBom)
            ->state([
                'material_id' => $material->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
            ])
            ->create();

        $response = $this->postJson('/api/v1/manufacturing/product-cost', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(200);
        // Should use active BOM with quantity 5
        $this->assertEquals(500.00, $response->json('data.total_cost'));
    }

    /**
     * Test getting product cost summary
     */
    public function test_user_can_get_product_cost_summary(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/manufacturing/products/{$product->id}/cost-summary");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test costing fails for non-existent material
     */
    public function test_calculate_cost_fails_with_invalid_material(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/manufacturing/material-cost', [
            'material_id' => 'nonexistent-uuid',
            'quantity' => 10,
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test costing fails for non-existent BOM
     */
    public function test_calculate_bom_cost_fails_with_invalid_bom(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/manufacturing/bom-cost', [
            'bom_id' => 'nonexistent-uuid',
            'quantity' => 1,
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test multi-tenant isolation for costing
     */
    public function test_costing_isolated_by_tenant(): void
    {
        $this->authenticateAs();

        $otherOrg = Organization::factory()->create();
        $otherMaterial = Material::factory()->for($otherOrg)->create();

        $response = $this->postJson('/api/v1/manufacturing/material-cost', [
            'material_id' => $otherMaterial->id,
            'quantity' => 10,
        ]);

        $this->assertThat(
            $response->status(),
            $this->logicalOr($this->equalTo(403), $this->equalTo(404))
        );
    }
}
