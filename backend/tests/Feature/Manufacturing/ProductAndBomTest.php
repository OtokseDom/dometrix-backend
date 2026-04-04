<?php

namespace Tests\Feature\Manufacturing;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Manufacturing\Models\Product;
use App\Domain\Manufacturing\Models\Bom;
use App\Domain\Manufacturing\Models\BomItem;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Units\Models\Units;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductAndBomTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing products
     */
    public function test_user_can_list_products(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $unit = Units::factory()->create();
        Product::factory()->count(3)->for($organization)->state(['unit_id' => $unit->id])->create();

        $response = $this->getJson('/api/v1/manufacturing/products');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Products retrieved']);
    }

    /**
     * Test creating a product
     */
    public function test_user_can_create_product(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $unit = Units::factory()->create();

        $response = $this->postJson('/api/v1/manufacturing/products', [
            'code' => 'PRD-001',
            'name' => 'Finished Widget',
            'description' => 'A finished product',
            'unit_id' => $unit->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created',
                'data' => [
                    'code' => 'PRD-001',
                    'name' => 'Finished Widget',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'organization_id' => $organization->id,
            'code' => 'PRD-001',
        ]);
    }

    /**
     * Test viewing a product
     */
    public function test_user_can_view_product(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/manufacturing/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['code' => $product->code]]);
    }

    /**
     * Test updating a product
     */
    public function test_user_can_update_product(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/manufacturing/products/{$product->id}", [
            'name' => 'Updated Product Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    /**
     * Test deleting a product
     */
    public function test_user_can_delete_product(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/manufacturing/products/{$product->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test listing BOMs
     */
    public function test_user_can_list_boms(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        Bom::factory()->count(3)->for($organization)->create();

        $response = $this->getJson('/api/v1/manufacturing/boms');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'BOMs retrieved']);
    }

    /**
     * Test creating a BOM
     */
    public function test_user_can_create_bom(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();

        $response = $this->postJson('/api/v1/manufacturing/boms', [
            'product_id' => $product->id,
            'version' => 1,
            'is_active' => false,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'BOM created',
            ]);

        $this->assertDatabaseHas('boms', [
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'version' => 1,
        ]);
    }

    /**
     * Test BOM can be activated
     */
    public function test_bom_can_be_activated(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $product = Product::factory()->for($organization)->create();
        $bom = Bom::factory()->for($organization)->for($product)->create(['is_active' => false]);

        $response = $this->patchJson("/api/v1/manufacturing/boms/{$bom->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('boms', [
            'id' => $bom->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test viewing a BOM
     */
    public function test_user_can_view_bom(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $bom = Bom::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/manufacturing/boms/{$bom->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'product_id', 'version']]);
    }

    /**
     * Test deleting a BOM
     */
    public function test_user_can_delete_bom(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $bom = Bom::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/manufacturing/boms/{$bom->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('boms', ['id' => $bom->id]);
    }

    /**
     * Test adding item to BOM
     */
    public function test_user_can_add_bom_item(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $bom = Bom::factory()->for($organization)->create();
        $material = Material::factory()->for($organization)->create();
        $unit = Units::factory()->create();

        $response = $this->postJson('/api/v1/manufacturing/bom-items', [
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'quantity' => 10,
            'unit_id' => $unit->id,
            'line_no' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'BOM item created']);

        $this->assertDatabaseHas('bom_items', [
            'organization_id' => $organization->id,
            'bom_id' => $bom->id,
            'material_id' => $material->id,
        ]);
    }

    /**
     * Test BOM item with sub-product
     */
    public function test_bom_item_can_reference_sub_product(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $bom = Bom::factory()->for($organization)->create();
        $subProduct = Product::factory()->for($organization)->create();
        $unit = Units::factory()->create();

        $response = $this->postJson('/api/v1/manufacturing/bom-items', [
            'bom_id' => $bom->id,
            'sub_product_id' => $subProduct->id,
            'quantity' => 2,
            'unit_id' => $unit->id,
            'line_no' => 1,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bom_items', [
            'bom_id' => $bom->id,
            'sub_product_id' => $subProduct->id,
        ]);
    }

    /**
     * Test updating BOM item
     */
    public function test_user_can_update_bom_item(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $bomItem = BomItem::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/manufacturing/bom-items/{$bomItem->id}", [
            'quantity' => 20,
            'wastage_percent' => 5.00,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bom_items', [
            'id' => $bomItem->id,
            'quantity' => 20,
            'wastage_percent' => 5.00,
        ]);
    }

    /**
     * Test deleting BOM item
     */
    public function test_user_can_delete_bom_item(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $bomItem = BomItem::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/manufacturing/bom-items/{$bomItem->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('bom_items', ['id' => $bomItem->id]);
    }
}
