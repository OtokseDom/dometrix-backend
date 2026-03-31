<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping MaterialSeeder.');
            return;
        }

        // Get category and unit IDs
        $categories = cache()->get('seeder_categories') ?? [];
        $rawMaterialCategoryId = $categories['raw_materials'] ?? DB::table('categories')
            ->where('code', 'RAW_MATERIALS')
            ->first()?->id;

        $packagingCategoryId = $categories['packaging'] ?? DB::table('categories')
            ->where('code', 'PACKAGING')
            ->first()?->id;

        // Get unit IDs by code
        $kgUnitId = DB::table('units')->where('code', 'kg')->first()?->id;
        $gUnitId = DB::table('units')->where('code', 'g')->first()?->id;
        $pcsUnitId = DB::table('units')->where('code', 'pcs')->first()?->id;

        if (!$kgUnitId || !$gUnitId || !$pcsUnitId) {
            $this->command->error('Required units not found. Make sure UnitSeeder has run.');
            return;
        }

        $now = Carbon::now();

        // Store material IDs for use in BomItemSeeder
        $materials = [
            'flour' => Str::uuid(),
            'sugar' => Str::uuid(),
            'eggs' => Str::uuid(),
            'butter' => Str::uuid(),
            'milk' => Str::uuid(),
            'salt' => Str::uuid(),
            'yeast' => Str::uuid(),
            'vanilla_extract' => Str::uuid(),
            'chocolate_powder' => Str::uuid(),
            'baking_powder' => Str::uuid(),
            'paper_bag' => Str::uuid(),
            'plastic_wrap' => Str::uuid(),
            'cardboard_box' => Str::uuid(),
        ];

        $materialInserts = [
            // Raw Materials
            [
                'id' => (string) $materials['flour'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_FLOUR',
                'name' => 'Wheat Flour',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $kgUnitId,
                'metadata' => json_encode(['supplier' => 'Miller Inc.', 'protein_content' => '12%']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['sugar'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_SUGAR',
                'name' => 'White Sugar',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $kgUnitId,
                'metadata' => json_encode(['type' => 'refined', 'crystal_size' => 'fine']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['eggs'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_EGGS',
                'name' => 'Fresh Eggs',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $pcsUnitId,
                'metadata' => json_encode(['size' => 'large', 'source' => 'farm']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['butter'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_BUTTER',
                'name' => 'Unsalted Butter',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $kgUnitId,
                'metadata' => json_encode(['type' => 'unsalted', 'origin' => 'Europe']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['milk'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_MILK',
                'name' => 'Whole Milk',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) DB::table('units')->where('code', 'l')->first()?->id ?? $kgUnitId,
                'metadata' => json_encode(['type' => 'whole', 'fat_content' => '3.5%']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['salt'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_SALT',
                'name' => 'Table Salt',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $gUnitId,
                'metadata' => json_encode(['type' => 'iodized']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['yeast'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_YEAST',
                'name' => 'Active Dry Yeast',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $gUnitId,
                'metadata' => json_encode(['type' => 'dry', 'storage' => 'refrigerated']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['vanilla_extract'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_VANILLA',
                'name' => 'Vanilla Extract',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $gUnitId,
                'metadata' => json_encode(['purity' => '99%', 'origin' => 'Madagascar']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['chocolate_powder'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_CHOCO',
                'name' => 'Cocoa Powder',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $kgUnitId,
                'metadata' => json_encode(['cocoa_content' => '20%', 'type' => 'unsweetened']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['baking_powder'],
                'organization_id' => (string) $organizationId,
                'code' => 'MAT_BAKING_POW',
                'name' => 'Baking Powder',
                'category_id' => $rawMaterialCategoryId,
                'unit_id' => (string) $gUnitId,
                'metadata' => json_encode(['type' => 'double-acting']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            // Packaging Materials
            [
                'id' => (string) $materials['paper_bag'],
                'organization_id' => (string) $organizationId,
                'code' => 'PKG_PAPER_BAG',
                'name' => 'Paper Carry Bag',
                'category_id' => $packagingCategoryId,
                'unit_id' => (string) $pcsUnitId,
                'metadata' => json_encode(['size' => 'medium', 'capacity' => '5kg']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['plastic_wrap'],
                'organization_id' => (string) $organizationId,
                'code' => 'PKG_PLASTIC_WRAP',
                'name' => 'Food Grade Plastic Wrap',
                'category_id' => $packagingCategoryId,
                'unit_id' => (string) $gUnitId,
                'metadata' => json_encode(['width' => '40cm', 'thickness' => '0.5mm']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $materials['cardboard_box'],
                'organization_id' => (string) $organizationId,
                'code' => 'PKG_CARDBOX',
                'name' => 'Cardboard Box',
                'category_id' => $packagingCategoryId,
                'unit_id' => (string) $pcsUnitId,
                'metadata' => json_encode(['size' => '20x20x10cm', 'corrugation' => 'double-wall']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('materials')->insert($materialInserts);

        // Store material IDs in cache for other seeders
        cache()->put('seeder_materials', $materials, now()->addHour());

        $this->command->info('✅ MaterialSeeder completed: ' . count($materials) . ' materials created.');
    }
}
