<?php

namespace App\Domain\Organization\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * OrganizationMasterDataService
 *
 * Provisions a new organization with all necessary master data to begin operations immediately.
 * This service generates:
 * - Roles and permissions
 * - Units of measurement
 * - Material and product categories
 * - Tax configurations
 * - Warehouses
 * - Currencies
 * - Starter products and materials
 * - Bill of Materials (BOMs)
 * - Material prices
 * - Organization settings
 *
 * All operations run within a database transaction for atomicity.
 */
class OrganizationMasterDataService
{
    /**
     * Generate all master data for a new organization.
     *
     * @param string $organizationId UUID of the organization
     * @return array{
     *     admin_role_id: string,
     *     employee_role_id: string,
     *     manager_role_id: string,
     *     status: string,
     *     message: string
     * }
     *
     * @throws Throwable if transaction fails
     */
    public function generate(string $organizationId): array
    {
        $result = [];

        DB::transaction(function () use ($organizationId, &$result) {
            // Skip if already processed (idempotent)
            $existingRoles = DB::table('roles')
                ->where('organization_id', $organizationId)
                ->count();

            if ($existingRoles > 0) {
                $adminRole = DB::table('roles')
                    ->where('organization_id', $organizationId)
                    ->where('name', 'Admin')
                    ->first();

                $result = [
                    'status' => 'skipped',
                    'message' => 'Master data already exists for this organization',
                    'admin_role_id' => $adminRole?->id,
                ];

                return;
            }

            $result = [
                'admin_role_id' => $this->generateRoles($organizationId),
                'employee_role_id' => DB::table('roles')
                    ->where('organization_id', $organizationId)
                    ->where('name', 'Employee')
                    ->first()?->id,
                'manager_role_id' => DB::table('roles')
                    ->where('organization_id', $organizationId)
                    ->where('name', 'Manager')
                    ->first()?->id,
                'status' => 'success',
                'message' => 'Master data generated successfully',
            ];

            // Generate all dependent data
            $this->generateUnits($organizationId);
            $this->generateCategories($organizationId);
            $this->generateTaxes($organizationId);
            $this->generateWarehouses($organizationId);
            $this->generateCurrencies($organizationId);
            $this->generateStarterProducts($organizationId);
            $this->generateStarterMaterials($organizationId);
            $this->generateBOMs($organizationId);
            $this->generateMaterialPrices($organizationId);
            $this->generateOrganizationSettings($organizationId);
        });

        return $result;
    }

    /**
     * Generate roles with default permissions structure.
     *
     * Returns the Admin role ID for use in auth flow.
     */
    protected function generateRoles(string $organizationId): string
    {
        $now = Carbon::now();

        $roles = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Admin',
                'permissions' => json_encode(['*']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Manager',
                'permissions' => json_encode([
                    'materials.view',
                    'materials.create',
                    'materials.edit',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'boms.view',
                    'boms.create',
                    'boms.edit',
                    'warehouse.view',
                    'warehouse.manage',
                    'reports.view',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'name' => 'Employee',
                'permissions' => json_encode([
                    'materials.view',
                    'products.view',
                    'boms.view',
                    'warehouse.view',
                    'reports.view',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('roles')->insert($roles);

        return $roles[0]['id'];
    }

    /**
     * Generate standard units of measurement for ERP operations.
     */
    protected function generateUnits(string $organizationId): void
    {
        $now = Carbon::now();

        // Check if units already exist globally (shared across orgs)
        $unitCount = DB::table('units')->count();

        if ($unitCount == 0) {
            $units = [
                // Quantity
                ['id' => Str::uuid(), 'code' => 'pcs', 'name' => 'Piece', 'type' => 'quantity', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                ['id' => Str::uuid(), 'code' => 'box', 'name' => 'Box', 'type' => 'quantity', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                ['id' => Str::uuid(), 'code' => 'pack', 'name' => 'Pack', 'type' => 'quantity', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                ['id' => Str::uuid(), 'code' => 'tray', 'name' => 'Tray', 'type' => 'quantity', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                // Weight
                ['id' => Str::uuid(), 'code' => 'kg', 'name' => 'Kilogram', 'type' => 'weight', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                ['id' => Str::uuid(), 'code' => 'g', 'name' => 'Gram', 'type' => 'weight', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                // Volume
                ['id' => Str::uuid(), 'code' => 'l', 'name' => 'Liter', 'type' => 'volume', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
                ['id' => Str::uuid(), 'code' => 'ml', 'name' => 'Milliliter', 'type' => 'volume', 'metadata' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ];

            DB::table('units')->insert($units);
        }
    }

    /**
     * Generate categories for materials and products.
     */
    protected function generateCategories(string $organizationId): void
    {
        $now = Carbon::now();

        $categories = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'RAW_MATERIALS',
                'name' => 'Raw Materials',
                'type' => 'material',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'Pure raw materials used in production']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'FINISHED_GOODS',
                'name' => 'Finished Goods',
                'type' => 'product',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'Ready-to-sell products']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'PACKAGING',
                'name' => 'Packaging Materials',
                'type' => 'material',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'Packaging and wrapping materials']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'CONSUMABLES',
                'name' => 'Consumables',
                'type' => 'material',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'Consumable items and supplies']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('categories')->insert($categories);
    }

    /**
     * Generate standard tax rates for the organization.
     */
    protected function generateTaxes(string $organizationId): void
    {
        $now = Carbon::now();

        $taxes = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'VAT_5',
                'name' => 'VAT 5%',
                'rate' => 5.00,
                'is_active' => true,
                'metadata' => json_encode(['default' => true, 'description' => 'Standard VAT rate']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'VAT_ZERO',
                'name' => 'Zero-Rated',
                'rate' => 0.00,
                'is_active' => true,
                'metadata' => json_encode(['description' => 'Zero-rated goods']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'VAT_EXEMPT',
                'name' => 'Exempt',
                'rate' => 0.00,
                'is_active' => true,
                'metadata' => json_encode(['description' => 'Tax-exempt sales']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('taxes')->insert($taxes);
    }

    /**
     * Generate standard warehouses for inventory management.
     */
    protected function generateWarehouses(string $organizationId): void
    {
        $now = Carbon::now();

        $warehouses = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'WH_RM',
                'name' => 'Raw Materials Warehouse',
                'type' => 'raw_material',
                'location' => 'Building A - Block 1',
                'is_active' => true,
                'manager_user_id' => null,
                'metadata' => json_encode(['capacity' => '1000 pallets', 'climate_controlled' => false]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'WH_WIP',
                'name' => 'Work In Progress Warehouse',
                'type' => 'wip',
                'location' => 'Building A - Block 2',
                'is_active' => true,
                'manager_user_id' => null,
                'metadata' => json_encode(['capacity' => '500 pallets', 'climate_controlled' => true]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'WH_FG',
                'name' => 'Finished Goods Warehouse',
                'type' => 'finished_goods',
                'location' => 'Building B - Block 1',
                'is_active' => true,
                'manager_user_id' => null,
                'metadata' => json_encode(['capacity' => '800 pallets', 'climate_controlled' => true]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('warehouses')->insert($warehouses);
    }

    /**
     * Link organization to base currency (default to AED).
     */
    protected function generateCurrencies(string $organizationId): void
    {
        // Currencies are typically shared globally - just ensure org record exists if needed
        // This is a placeholder for any currency-organization relationship if exists
    }

    /**
     * Generate starter products for the organization.
     * Returns mapping of product codes to UUIDs for BOM generation.
     */
    protected function generateStarterProducts(string $organizationId): array
    {
        $now = Carbon::now();

        // Get category and unit IDs
        $finishedGoodsCategory = DB::table('categories')
            ->where('organization_id', $organizationId)
            ->where('code', 'FINISHED_GOODS')
            ->first();

        $pcsUnit = DB::table('units')->where('code', 'pcs')->first();

        if (!$finishedGoodsCategory || !$pcsUnit) {
            return [];
        }

        $products = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'PROD_BREAD',
                'name' => 'Whole Wheat Bread Loaf',
                'description' => 'Fresh baked whole wheat bread loaf, 500g',
                'unit_id' => $pcsUnit->id,
                'metadata' => json_encode(['weight' => '500g', 'shelf_life_days' => 3]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'PROD_CROISSANT',
                'name' => 'Butter Croissant',
                'description' => 'Flaky butter croissant, 75g each',
                'unit_id' => $pcsUnit->id,
                'metadata' => json_encode(['weight' => '75g', 'shelf_life_days' => 2]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'PROD_CAKE',
                'name' => 'Vanilla Sponge Cake',
                'description' => 'Moist vanilla sponge cake, 800g',
                'unit_id' => $pcsUnit->id,
                'metadata' => json_encode(['weight' => '800g', 'shelf_life_days' => 5, 'requires_refrigeration' => true]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('products')->insert($products);

        return collect($products)->keyBy('code')->map(fn($p) => $p['id'])->toArray();
    }

    /**
     * Generate starter materials for production.
     * Returns mapping of material codes to UUIDs for BOM items.
     */
    protected function generateStarterMaterials(string $organizationId): array
    {
        $now = Carbon::now();

        $rawMaterialCategory = DB::table('categories')
            ->where('organization_id', $organizationId)
            ->where('code', 'RAW_MATERIALS')
            ->first();

        $packagingCategory = DB::table('categories')
            ->where('organization_id', $organizationId)
            ->where('code', 'PACKAGING')
            ->first();

        $kgUnit = DB::table('units')->where('code', 'kg')->first();
        $gUnit = DB::table('units')->where('code', 'g')->first();
        $pcsUnit = DB::table('units')->where('code', 'pcs')->first();
        $lUnit = DB::table('units')->where('code', 'l')->first();

        if (!$rawMaterialCategory || !$kgUnit || !$gUnit || !$pcsUnit) {
            return [];
        }

        $materials = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_FLOUR',
                'name' => 'Wheat Flour',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $kgUnit->id,
                'metadata' => json_encode(['supplier' => 'TBD', 'protein_content' => '12%']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_SUGAR',
                'name' => 'White Sugar',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $kgUnit->id,
                'metadata' => json_encode(['type' => 'refined']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_EGGS',
                'name' => 'Fresh Eggs',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $pcsUnit->id,
                'metadata' => json_encode(['size' => 'large']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_BUTTER',
                'name' => 'Unsalted Butter',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $kgUnit->id,
                'metadata' => json_encode(['type' => 'unsalted']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_MILK',
                'name' => 'Whole Milk',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $lUnit?->id ?? $kgUnit->id,
                'metadata' => json_encode(['type' => 'whole', 'fat_content' => '3.5%']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_SALT',
                'name' => 'Table Salt',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $gUnit->id,
                'metadata' => json_encode(['type' => 'iodized']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_YEAST',
                'name' => 'Active Dry Yeast',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $gUnit->id,
                'metadata' => json_encode(['type' => 'dry', 'storage' => 'refrigerated']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_VANILLA',
                'name' => 'Vanilla Extract',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $gUnit->id,
                'metadata' => json_encode(['purity' => '99%']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'MAT_BP',
                'name' => 'Baking Powder',
                'category_id' => $rawMaterialCategory->id,
                'unit_id' => $gUnit->id,
                'metadata' => json_encode(['type' => 'double-acting']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            // Packaging
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'code' => 'PKG_PAPER_BAG',
                'name' => 'Paper Carry Bag',
                'category_id' => $packagingCategory->id,
                'unit_id' => $pcsUnit->id,
                'metadata' => json_encode(['size' => 'medium']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('materials')->insert($materials);

        return collect($materials)->keyBy('code')->map(fn($m) => $m['id'])->toArray();
    }

    /**
     * Generate starter BOMs for initial products.
     */
    protected function generateBOMs(string $organizationId): void
    {
        $now = Carbon::now();

        // Get product IDs
        $breadProduct = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('code', 'PROD_BREAD')
            ->first();

        $croissantProduct = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('code', 'PROD_CROISSANT')
            ->first();

        $cakeProduct = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('code', 'PROD_CAKE')
            ->first();

        if (!$breadProduct || !$croissantProduct || !$cakeProduct) {
            return;
        }

        $boms = [
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'product_id' => $breadProduct->id,
                'version' => '1.0',
                'is_active' => true,
                'metadata' => json_encode(['process' => 'standard baking', 'baking_temp' => '200°C', 'baking_time' => '35 minutes']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'product_id' => $croissantProduct->id,
                'version' => '1.0',
                'is_active' => true,
                'metadata' => json_encode(['process' => 'lamination', 'baking_temp' => '190°C', 'baking_time' => '18 minutes']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'product_id' => $cakeProduct->id,
                'version' => '1.0',
                'is_active' => true,
                'metadata' => json_encode(['process' => 'creaming method', 'baking_temp' => '170°C', 'baking_time' => '45 minutes']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('boms')->insert($boms);

        // Store BOM IDs for BOM items generation
        cache()->put("org_boms_{$organizationId}", collect($boms)->keyBy('product_id')->toArray(), now()->addHour());
    }

    /**
     * Generate BOM items for each starter BOM.
     */
    protected function generateBomItems(string $organizationId): void
    {
        $now = Carbon::now();

        // Get material IDs
        $materials = DB::table('materials')
            ->where('organization_id', $organizationId)
            ->pluck('id', 'code');

        // Get BOM IDs
        $boms = DB::table('boms')
            ->where('organization_id', $organizationId)
            ->get()
            ->keyBy('product_id');

        // Get products for BOM lookup
        $breadProduct = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('code', 'PROD_BREAD')
            ->first();

        $croissantProduct = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('code', 'PROD_CROISSANT')
            ->first();

        $cakeProduct = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('code', 'PROD_CAKE')
            ->first();

        // Get units
        $gUnit = DB::table('units')->where('code', 'g')->first();
        $kgUnit = DB::table('units')->where('code', 'kg')->first();
        $pcsUnit = DB::table('units')->where('code', 'pcs')->first();
        $lUnit = DB::table('units')->where('code', 'l')->first();

        if (empty($materials) || empty($boms) || !$gUnit || !$kgUnit) {
            return;
        }

        $bomItems = [
            // ===== BREAD LOAF (500g) =====
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$breadProduct->id]?->id,
                'material_id' => $materials['MAT_FLOUR'] ?? null,
                'sub_product_id' => null,
                'quantity' => 400,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 2.5,
                'line_no' => 1,
                'metadata' => json_encode(['description' => 'Main ingredient']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$breadProduct->id]?->id,
                'material_id' => $materials['MAT_BUTTER'] ?? null,
                'sub_product_id' => null,
                'quantity' => 0.020,
                'unit_id' => $kgUnit->id,
                'wastage_percent' => 1.0,
                'line_no' => 2,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$breadProduct->id]?->id,
                'material_id' => $materials['MAT_SALT'] ?? null,
                'sub_product_id' => null,
                'quantity' => 10,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 0.5,
                'line_no' => 3,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$breadProduct->id]?->id,
                'material_id' => $materials['MAT_YEAST'] ?? null,
                'sub_product_id' => null,
                'quantity' => 5,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 0,
                'line_no' => 4,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],

            // ===== CROISSANT (75g) =====
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$croissantProduct->id]?->id,
                'material_id' => $materials['MAT_FLOUR'] ?? null,
                'sub_product_id' => null,
                'quantity' => 45,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 3.0,
                'line_no' => 1,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$croissantProduct->id]?->id,
                'material_id' => $materials['MAT_BUTTER'] ?? null,
                'sub_product_id' => null,
                'quantity' => 0.030,
                'unit_id' => $kgUnit->id,
                'wastage_percent' => 2.0,
                'line_no' => 2,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$croissantProduct->id]?->id,
                'material_id' => $materials['MAT_SUGAR'] ?? null,
                'sub_product_id' => null,
                'quantity' => 8,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 1.0,
                'line_no' => 3,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],

            // ===== CAKE (800g) =====
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_FLOUR'] ?? null,
                'sub_product_id' => null,
                'quantity' => 200,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 2.0,
                'line_no' => 1,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_SUGAR'] ?? null,
                'sub_product_id' => null,
                'quantity' => 250,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 1.5,
                'line_no' => 2,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_EGGS'] ?? null,
                'sub_product_id' => null,
                'quantity' => 4,
                'unit_id' => $pcsUnit->id,
                'wastage_percent' => 5.0,
                'line_no' => 3,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_BUTTER'] ?? null,
                'sub_product_id' => null,
                'quantity' => 0.150,
                'unit_id' => $kgUnit->id,
                'wastage_percent' => 1.0,
                'line_no' => 4,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_MILK'] ?? null,
                'sub_product_id' => null,
                'quantity' => 0.100,
                'unit_id' => $lUnit?->id ?? $kgUnit->id,
                'wastage_percent' => 2.0,
                'line_no' => 5,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_VANILLA'] ?? null,
                'sub_product_id' => null,
                'quantity' => 5,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 0,
                'line_no' => 6,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $organizationId,
                'bom_id' => $boms[$cakeProduct->id]?->id,
                'material_id' => $materials['MAT_BP'] ?? null,
                'sub_product_id' => null,
                'quantity' => 8,
                'unit_id' => $gUnit->id,
                'wastage_percent' => 0,
                'line_no' => 7,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        // Filter out null material_ids
        $bomItems = array_filter($bomItems, fn($item) => $item['material_id'] !== null);

        if (!empty($bomItems)) {
            DB::table('bom_items')->insert($bomItems);
        }
    }

    /**
     * Generate material prices for starter materials.
     */
    protected function generateMaterialPrices(string $organizationId): void
    {
        $now = Carbon::now();

        $materials = DB::table('materials')
            ->where('organization_id', $organizationId)
            ->get()
            ->keyBy('code');

        // Default realistic prices for bakery materials (in local currency)
        $priceMap = [
            'MAT_FLOUR' => 1.25,      // per kg
            'MAT_SUGAR' => 1.50,      // per kg
            'MAT_EGGS' => 0.35,       // per piece
            'MAT_BUTTER' => 4.75,     // per kg
            'MAT_MILK' => 0.85,       // per liter
            'MAT_SALT' => 0.015,      // per gram
            'MAT_YEAST' => 0.085,     // per gram
            'MAT_VANILLA' => 0.175,   // per gram
            'MAT_BP' => 0.035,        // per gram
            'PKG_PAPER_BAG' => 0.125, // per piece
        ];

        $prices = [];
        foreach ($priceMap as $code => $price) {
            if (isset($materials[$code])) {
                $prices[] = [
                    'id' => Str::uuid(),
                    'organization_id' => $organizationId,
                    'material_id' => $materials[$code]->id,
                    'price' => $price,
                    'effective_date' => now()->toDateString(),
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
        }

        if (!empty($prices)) {
            DB::table('material_prices')->insert($prices);
        }
    }

    /**
     * Generate organization settings with defaults.
     */
    protected function generateOrganizationSettings(string $organizationId): void
    {
        $now = Carbon::now();

        // Get defaults
        $baseCurrency = DB::table('currencies')->where('code', 'AED')->first();
        $defaultTax = DB::table('taxes')
            ->where('organization_id', $organizationId)
            ->where('code', 'VAT_5')
            ->first();
        $defaultWarehouse = DB::table('warehouses')
            ->where('organization_id', $organizationId)
            ->where('code', 'WH_FG')
            ->first();

        $settings = [
            'id' => Str::uuid(),
            'organization_id' => $organizationId,
            'inventory_method' => 'fifo',
            'costing_method' => 'weighted_average',
            'allow_negative_stock' => false,
            'tax_inclusive_pricing' => false,
            'base_currency_id' => $baseCurrency?->id,
            'default_tax_id' => $defaultTax?->id,
            'default_warehouse_id' => $defaultWarehouse?->id,
            'decimal_precision' => 4,
            'timezone' => 'Asia/Dubai',
            'metadata' => json_encode([
                'production' => [
                    'auto_close_order' => false,
                    'default_shift' => 'morning',
                    'auto_allocate_materials' => true,
                ],
                'inventory' => [
                    'enable_batch_tracking' => false,
                    'enable_serial_tracking' => false,
                    'reorder_point_method' => 'automatic',
                ],
                'sales' => [
                    'quotation_prefix' => 'QT',
                    'invoice_prefix' => 'INV',
                    'enable_credit_sales' => false,
                    'credit_limit' => 0,
                ],
                'purchasing' => [
                    'po_prefix' => 'PO',
                    'auto_receive' => false,
                ],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('settings')->insert($settings);
    }
}
