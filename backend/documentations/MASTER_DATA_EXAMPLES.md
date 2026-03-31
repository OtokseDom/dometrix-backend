```php
/**
* QUICK START: OrganizationMasterDataService Integration
*
* This file shows practical examples of how to use the master data generator service.
*/

// ============================================
// Example 1: Automatic Integration (Default)
// ============================================
// The service is automatically called during registration.
// No additional code needed - it just works!

// User registers → Organization created → Master data auto-generated ✅


// ============================================
// Example 2: Manual Usage in Controller
// ============================================

<?php

namespace App\Http\Controllers;

use App\Domain\Organization\Services\OrganizationMasterDataService;
use App\Domain\Organization\Models\Organization;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationMasterDataService $masterDataService
    ) {}

    /**
     * Provision master data for an existing organization.
     * Useful if you need to re-generate or fix master data.
     */
    public function provisioning(Organization $org)
    {
        try {
            $result = $this->masterDataService->generate($org->id);

            return response()->json([
                'success' => $result['status'] === 'success',
                'message' => $result['message'],
                'admin_role_id' => $result['admin_role_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}


// ============================================
// Example 3: Service Provider Registration
// ============================================
<?php

namespace App\Providers;

use App\Domain\Organization\Services\OrganizationMasterDataService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Auto-wire the service
        $this->app->singleton(
            OrganizationMasterDataService::class,
            fn ($app) => new OrganizationMasterDataService()
        );
    }
}
// Or use auto-registration (modern Laravel):
// Laravel 11+ automatically registers services with type hints ✅


// ============================================
// Example 4: Testing Master Data Generation
// ============================================

<?php

namespace Tests\Feature;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Services\OrganizationMasterDataService;
use Tests\TestCase;

class MasterDataGenerationTest extends TestCase
{
    public function test_master_data_generation(): void
    {
        // Create an organization
        $org = Organization::create([
            'name' => 'Test Bakery',
            'code' => 'test-bakery',
        ]);

        // Generate master data
        $service = app(OrganizationMasterDataService::class);
        $result = $service->generate($org->id);

        // Assert success
        $this->assertEquals('success', $result['status']);
        $this->assertNotNull($result['admin_role_id']);

        // Verify roles created
        $roles = \DB::table('roles')
            ->where('organization_id', $org->id)
            ->pluck('name')
            ->toArray();

        $this->assertContains('Admin', $roles);
        $this->assertContains('Manager', $roles);
        $this->assertContains('Employee', $roles);

        // Verify products created
        $products = \DB::table('products')
            ->where('organization_id', $org->id)
            ->count();

        $this->assertEquals(3, $products);

        // Verify warehouses created
        $warehouses = \DB::table('warehouses')
            ->where('organization_id', $org->id)
            ->pluck('code')
            ->toArray();

        $this->assertContains('WH_RM', $warehouses);
        $this->assertContains('WH_WIP', $warehouses);
        $this->assertContains('WH_FG', $warehouses);

        // Verify BOMs created
        $boms = \DB::table('boms')
            ->where('organization_id', $org->id)
            ->count();

        $this->assertEquals(3, $boms);
    }

    public function test_master_data_idempotent(): void
    {
        $org = Organization::create([
            'name' => 'Idempotent Test',
            'code' => 'idempotent',
        ]);

        $service = app(OrganizationMasterDataService::class);

        // First generation
        $result1 = $service->generate($org->id);
        $this->assertEquals('success', $result1['status']);

        // Second generation (should skip)
        $result2 = $service->generate($org->id);
        $this->assertEquals('skipped', $result2['status']);

        // Verify no duplicates
        $roleCount = \DB::table('roles')
            ->where('organization_id', $org->id)
            ->count();

        $this->assertEquals(3, $roleCount); // Still 3, not 6
    }
}


// ============================================
// Example 5: Custom Extension
// ============================================

<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\Services\OrganizationMasterDataService;

/**
 * Extended version for different business models.
 * Inherit and override specific methods.
 */
class RetailOrganizationMasterDataService extends OrganizationMasterDataService
{
    /**
     * Override warehouse generation for retail business.
     */
    protected function generateWarehouses(string $organizationId): void
    {
        $now = \Carbon\Carbon::now();

        $warehouses = [
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'STORE_MAIN',
                'name' => 'Main Store',
                'type' => 'retail',
                'location' => 'Main Street',
                'is_active' => true,
                'manager_user_id' => null,
                'metadata' => json_encode(['store_number' => '001']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            // ... retail-specific warehouses
        ];

        \Illuminate\Support\Facades\DB::table('warehouses')->insert($warehouses);
    }
}

// Usage:
// $retailService = app(RetailOrganizationMasterDataService::class);
// $retailService->generate($org->id);


// ============================================
// Example 6: API Endpoint for Re-provisioning
// ============================================

<?php

namespace App\Http\Controllers\Api;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Services\OrganizationMasterDataService;
use Illuminate\Http\JsonResponse;

class MasterDataController
{
    public function __construct(
        private OrganizationMasterDataService $service
    ) {}

    /**
     * POST /api/organizations/{org}/master-data/regenerate
     *
     * Regenerate master data if corrupted or missing.
     * Only accessible to organization admins.
     */
    public function regenerate(Organization $org): JsonResponse
    {
        // Check authorization (org admin only)
        $this->authorize('update', $org);

        try {
            $result = $this->service->generate($org->id);

            return response()->json($result);
        } catch (\Throwable $e) {
            \Log::error('Master data regeneration failed', [
                'org_id' => $org->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to regenerate master data',
            ], 500);
        }
    }

    /**
     * GET /api/organizations/{org}/master-data/status
     *
     * Check what master data exists for an organization.
     */
    public function status(Organization $org): JsonResponse
    {
        return response()->json([
            'organization_id' => $org->id,
            'roles' => \DB::table('roles')
                ->where('organization_id', $org->id)
                ->count(),
            'products' => \DB::table('products')
                ->where('organization_id', $org->id)
                ->count(),
            'materials' => \DB::table('materials')
                ->where('organization_id', $org->id)
                ->count(),
            'boms' => \DB::table('boms')
                ->where('organization_id', $org->id)
                ->count(),
            'warehouses' => \DB::table('warehouses')
                ->where('organization_id', $org->id)
                ->count(),
            'categories' => \DB::table('categories')
                ->where('organization_id', $org->id)
                ->count(),
            'settings' => \DB::table('settings')
                ->where('organization_id', $org->id)
                ->exists(),
        ]);
    }
}


// ============================================
// What Gets Generated (Reference)
// ============================================

/**
 * COMPLETE DATA STRUCTURE CREATED
 *
 * After service->generate($org->id):
 *
 * ├── Roles (3)
 * │   ├── Admin (permissions: *)
 * │   ├── Manager (operational permissions)
 * │   └── Employee (view permissions)
 * │
 * ├── Units (8)
 * │   ├── Quantity: pcs, box, pack, tray
 * │   ├── Weight: kg, g
 * │   └── Volume: l, ml
 * │
 * ├── Categories (4)
 * │   ├── Raw Materials
 * │   ├── Finished Goods
 * │   ├── Packaging Materials
 * │   └── Consumables
 * │
 * ├── Taxes (3)
 * │   ├── VAT 5% (default)
 * │   ├── Zero-Rated
 * │   └── Exempt
 * │
 * ├── Warehouses (3)
 * │   ├── Raw Materials (WH_RM)
 * │   ├── Work In Progress (WH_WIP)
 * │   └── Finished Goods (WH_FG)
 * │
 * ├── Products (3 - Starter Products)
 * │   ├── Whole Wheat Bread Loaf
 * │   ├── Butter Croissant
 * │   └── Vanilla Sponge Cake
 * │
 * ├── Materials (10 - Starter Materials)
 * │   ├── Raw: Flour, Sugar, Eggs, Butter, Milk, Salt, Yeast, Vanilla, Baking Powder
 * │   └── Packaging: Paper Bag
 * │
 * ├── BOMs (3)
 * │   ├── Bread Loaf BOM v1.0
 * │   ├── Croissant BOM v1.0
 * │   └── Cake BOM v1.0
 * │
 * ├── BOM Items (12)
 * │   ├── Bread: 4 items
 * │   ├── Croissant: 3 items
 * │   └── Cake: 7 items
 * │
 * ├── Material Prices (10)
 * │   └── Realistic bakery pricing
 * │
 * └── Settings (1)
 *     ├── Inventory: FIFO
 *     ├── Costing: Weighted Average
 *     ├── Currency: AED
 *     ├── Timezone: Asia/Dubai
 *     └── Metadata: Production, Sales, Purchasing configs
 */


// ============================================
// Dependency Injection (Recommended)
// ============================================

// In your controller constructor:
public function __construct(
    private OrganizationMasterDataService $masterDataService
) {}

// OR in service:
public function __construct(
    private readonly OrganizationMasterDataService $service
) {}

// The service is automatically registered via Laravel's
// automatic service resolution. No manual binding needed! ✅


// ============================================
// Error Handling
// ============================================

try {
    $result = $service->generate($org->id);

    if ($result['status'] === 'success') {
        // All master data created
        $adminRoleId = $result['admin_role_id'];
    } elseif ($result['status'] === 'skipped') {
        // Already provisioned
        \Log::info('Master data already exists: ' . $result['message']);
    }
} catch (\Throwable $e) {
    // Transaction automatically rolled back
    // No partial data in database
    \Log::error('Master data generation failed', [
        'org_id' => $org->id,
        'error' => $e->getMessage(),
    ]);

    throw $e; // Re-throw or handle as needed
}


// ============================================
// Environment-Specific Configuration
// ============================================

// For different regions/use cases, you can:

// 1. Create subclasses
class MexicoOrganizationMasterData extends OrganizationMasterDataService {
    // Override generateTaxes(), generateWarehouses(), etc.
}

// 2. Use configuration
$service = app(OrganizationMasterDataService::class);
// Service automatically uses config('app.timezone'), etc.

// 3. Extend via traits
trait RetailMasterData {
    protected function generateRetailCategories() { ... }
}

// All approaches work with the current architecture! 🎯
```
