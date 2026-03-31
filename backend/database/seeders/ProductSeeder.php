<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping ProductSeeder.');
            return;
        }

        // Get category and unit IDs
        $categories = cache()->get('seeder_categories') ?? [];
        $finishedGoodsCategoryId = $categories['finished_goods'] ?? DB::table('categories')
            ->where('code', 'FINISHED_GOODS')
            ->first()?->id;

        $pcsUnitId = DB::table('units')->where('code', 'pcs')->first()?->id;
        $kgUnitId = DB::table('units')->where('code', 'kg')->first()?->id;

        if (!$pcsUnitId || !$kgUnitId) {
            $this->command->error('Required units not found. Make sure UnitSeeder has run.');
            return;
        }

        $now = Carbon::now();

        // Store product IDs for use in BomSeeder and BomItemSeeder
        $products = [
            'bread_loaf' => Str::uuid(),
            'sponge_cake' => Str::uuid(),
            'croissant' => Str::uuid(),
        ];

        $productInserts = [
            [
                'id' => (string) $products['bread_loaf'],
                'organization_id' => (string) $organizationId,
                'code' => 'PROD_BREAD',
                'name' => 'Whole Wheat Bread Loaf',
                'description' => 'Fresh baked whole wheat bread loaf, 500g',
                'unit_id' => (string) $pcsUnitId,
                'metadata' => json_encode(['weight' => '500g', 'shelf_life_days' => 3, 'sku_prefix' => 'BRD']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $products['sponge_cake'],
                'organization_id' => (string) $organizationId,
                'code' => 'PROD_SPONGE',
                'name' => 'Vanilla Sponge Cake',
                'description' => 'Moist vanilla sponge cake, 800g',
                'unit_id' => (string) $pcsUnitId,
                'metadata' => json_encode(['weight' => '800g', 'shelf_life_days' => 5, 'requires_refrigeration' => true, 'sku_prefix' => 'SPN']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $products['croissant'],
                'organization_id' => (string) $organizationId,
                'code' => 'PROD_CROISSANT',
                'name' => 'Butter Croissant',
                'description' => 'Flaky butter croissant, 75g each',
                'unit_id' => (string) $pcsUnitId,
                'metadata' => json_encode(['weight' => '75g', 'shelf_life_days' => 2, 'sku_prefix' => 'CRS']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('products')->insert($productInserts);

        // Store product IDs in cache for other seeders
        cache()->put('seeder_products', $products, now()->addHour());

        $this->command->info('✅ ProductSeeder completed: ' . count($products) . ' products created.');
    }
}
