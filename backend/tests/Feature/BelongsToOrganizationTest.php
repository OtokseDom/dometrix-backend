<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\Manufacturing\Models\Product;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Inventory\Models\InventoryCostLayer;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Role\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class BelongsToOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org1;
    private Organization $org2;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organizations
        $this->org1 = Organization::factory()->create(['name' => 'Organization One']);
        $this->org2 = Organization::factory()->create(['name' => 'Organization Two']);

        // Create users
        $this->user1 = User::factory()->create(['name' => 'User One']);
        $this->user2 = User::factory()->create(['name' => 'User Two']);

        // Assign users to organizations
        $role = Role::factory()->admin()->for($this->org1)->create();
        OrganizationUser::create([
            'organization_id' => $this->org1->id,
            'user_id' => $this->user1->id,
            'role_id' => $role->id,
            'status' => 'active'
        ]);

        $role2 = Role::factory()->admin()->for($this->org2)->create();
        OrganizationUser::create([
            'organization_id' => $this->org2->id,
            'user_id' => $this->user2->id,
            'role_id' => $role2->id,
            'status' => 'active'
        ]);
    }

    /**
     * Test that queries are automatically scoped to the authenticated user's organization
     */
    public function test_queries_are_automatically_scoped_by_organization(): void
    {
        // Create products in both organizations
        $product1 = Product::factory()->for($this->org1)->create(['name' => 'Product 1']);
        $product2 = Product::factory()->for($this->org2)->create(['name' => 'Product 2']);

        // Authenticate as user1 (org1)
        Auth::login($this->user1);

        // Query should only return products from org1
        $products = Product::all();
        $this->assertCount(1, $products);
        $this->assertEquals($product1->id, $products[0]->id);
        $this->assertEquals($this->org1->id, $products[0]->organization_id);
    }

    /**
     * Test that a different authenticated user sees only their organization's data
     */
    public function test_different_user_sees_only_their_organization(): void
    {
        // Create products in both organizations
        Product::factory()->for($this->org1)->count(3)->create();
        Product::factory()->for($this->org2)->count(2)->create();

        // User1 (org1) sees 3 products
        Auth::login($this->user1);
        $this->assertCount(3, Product::all());

        // User2 (org2) sees 2 products
        Auth::logout();
        Auth::login($this->user2);
        $this->assertCount(2, Product::all());
    }

    /**
     * Test that organization_id is automatically assigned during creation
     */
    public function test_organization_id_is_auto_assigned_on_create(): void
    {
        Auth::login($this->user1);

        // Create a product without specifying organization_id
        $product = Product::create([
            'code' => 'PROD-001',
            'name' => 'Test Product',
            'unit_id' => null,
        ]);

        // Verify organization_id was auto-assigned
        $this->assertEquals($this->org1->id, $product->organization_id);
    }

    /**
     * Test that explicitly set organization_id is preserved
     */
    public function test_explicit_organization_id_is_preserved(): void
    {
        Auth::login($this->user1);

        // Create a product with an explicit (but different) organization_id
        $product = Product::create([
            'code' => 'PROD-002',
            'name' => 'Test Product',
            'organization_id' => $this->org2->id,
            'unit_id' => null,
        ]);

        // Explicit org_id should be preserved
        $this->assertEquals($this->org2->id, $product->organization_id);
    }

    /**
     * Test that no scope is applied when not authenticated
     */
    public function test_no_scope_applied_when_not_authenticated(): void
    {
        Auth::logout();

        // Create products in both organizations without auth
        // (this should work because global scope doesn't apply without auth)
        Product::factory()->for($this->org1)->create();
        Product::factory()->for($this->org2)->create();

        // When not authenticated, all products should be visible
        $this->assertCount(2, Product::all());
    }

    /**
     * Test that withoutGlobalScope can bypass the organization filter
     */
    public function test_without_global_scope_bypasses_organization_filter(): void
    {
        // Create products in both organizations
        Product::factory()->for($this->org1)->count(2)->create();
        Product::factory()->for($this->org2)->count(3)->create();

        // Authenticate as user1
        Auth::login($this->user1);

        // Normal query sees only org1 products
        $this->assertCount(2, Product::all());

        // Bypassing the scope sees all products
        $this->assertCount(5, Product::withoutGlobalScope('organization')->get());
    }

    /**
     * Test that the scope works with multiple tenant-owned models
     */
    public function test_scope_works_across_all_tenant_models(): void
    {
        Auth::login($this->user1);

        // Create materials in org1
        $materials1 = Material::factory()->for($this->org1)->count(2)->create();
        // Create materials in org2
        Material::factory()->for($this->org2)->count(1)->create();

        // Should see only org1 materials
        $this->assertCount(2, Material::all());

        // Create warehouses in org1
        $warehouses1 = Warehouse::factory()->for($this->org1)->count(2)->create();
        // Create warehouses in org2
        Warehouse::factory()->for($this->org2)->count(1)->create();

        // Should see only org1 warehouses
        $this->assertCount(2, Warehouse::all());
    }

    /**
     * Test that relationships still work correctly with the global scope
     */
    public function test_relationships_work_with_global_scope(): void
    {
        Auth::login($this->user1);

        // Create a product and materials
        $product = Product::factory()->for($this->org1)->create();
        $material = Material::factory()->for($this->org1)->create();

        // Load relationships - should work fine with scope
        $product = Product::with('boms')->first();
        $this->assertNotNull($product);

        $material = Material::with('prices')->first();
        $this->assertNotNull($material);
    }

    /**
     * Test that eager loading doesn't break with global scope
     */
    public function test_eager_loading_works_with_global_scope(): void
    {
        Auth::login($this->user1);

        // Create products with relationships
        $products = Product::factory()->for($this->org1)->count(3)->create();
        foreach ($products as $product) {
            $product->boms()->create(['version' => 1, 'is_active' => true]);
        }

        // Eager loading should work
        $productsWithBoms = Product::with('boms')->get();
        $this->assertCount(3, $productsWithBoms);
        foreach ($productsWithBoms as $product) {
            $this->assertNotNull($product->boms);
        }
    }

    /**
     * Test that inventory models are properly scoped
     */
    public function test_inventory_models_are_properly_scoped(): void
    {
        Auth::login($this->user1);

        // Create inventory batches in org1
        $batch1 = InventoryBatch::factory()->for($this->org1)->create();
        // Create a batch in org2
        InventoryBatch::factory()->for($this->org2)->create();

        // Should see only org1 batches
        $this->assertCount(1, InventoryBatch::all());

        // Verify scopes still work
        $batch1->update(['status' => 'ACTIVE']);
        $this->assertCount(1, InventoryBatch::active()->get());
    }

    /**
     * Test that AuditLog scope changes still work
     */
    public function test_audit_log_scope_changes_work(): void
    {
        Auth::login($this->user1);

        // Create audit logs
        $auditLog1 = AuditLog::factory()->for($this->org1)->create(['module' => 'Inventory']);
        $auditLog2 = AuditLog::factory()->for($this->org1)->create(['module' => 'Manufacturing']);
        AuditLog::factory()->for($this->org2)->create(['module' => 'Inventory']);

        // Should see only org1 audit logs
        $this->assertCount(2, AuditLog::all());

        // Module scope should still work
        $this->assertCount(1, AuditLog::module('Inventory')->get());
        $this->assertCount(1, AuditLog::module('Manufacturing')->get());
    }

    /**
     * Test that manufacturing inventory relationship paths still work
     */
    public function test_complex_relationship_paths_work(): void
    {
        Auth::login($this->user1);

        // Create complex relationship structure
        $warehouse = Warehouse::factory()->for($this->org1)->create();
        $material = Material::factory()->for($this->org1)->create();
        $batch = InventoryBatch::factory()
            ->for($this->org1)
            ->for($material)
            ->for($warehouse)
            ->create();

        $costLayer = InventoryCostLayer::factory()
            ->for($this->org1)
            ->for($warehouse)
            ->for($material)
            ->for($batch)
            ->create();

        // All queries should be filtered
        $this->assertCount(1, InventoryCostLayer::all());
        $this->assertCount(1, InventoryBatch::all());

        // Relationship access should work
        $batch = InventoryBatch::first();
        $this->assertEquals($warehouse->id, $batch->warehouse_id);
        $this->assertEquals($material->id, $batch->material_id);
    }

    /**
     * Test that console commands work without authentication breaking
     */
    public function test_console_commands_can_access_unscoped_data(): void
    {
        // Without authentication, queries should not be scoped
        Auth::logout();

        // Create data in both organizations
        Product::factory()->for($this->org1)->create();
        Product::factory()->for($this->org2)->create();

        // Should see all products
        $this->assertCount(2, Product::all());
    }
}
