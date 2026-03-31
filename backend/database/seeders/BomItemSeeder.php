<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BomItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping BomItemSeeder.');
            return;
        }

        // Get cached BOMs and materials
        $boms = cache()->get('seeder_boms') ?? [];
        $materials = cache()->get('seeder_materials') ?? [];
        $products = cache()->get('seeder_products') ?? [];

        // Get BOM IDs from database as fallback
        $breadBomId = $boms['bread_loaf_v1'] ?? DB::table('boms')
            ->where('version', '1.0')
            ->whereHas('product', fn ($q) => $q->where('code', 'PROD_BREAD'))
            ->first()?->id;

        $cakeBomId = $boms['sponge_cake_v1'] ?? DB::table('boms')
            ->where('version', '1.0')
            ->whereHas('product', fn ($q) => $q->where('code', 'PROD_SPONGE'))
            ->first()?->id;

        $croissantBomId = $boms['croissant_v1'] ?? DB::table('boms')
            ->where('version', '1.0')
            ->whereHas('product', fn ($q) => $q->where('code', 'PROD_CROISSANT'))
            ->first()?->id;

        // Get material IDs from database as fallback
        $flourId = $materials['flour'] ?? DB::table('materials')->where('code', 'MAT_FLOUR')->first()?->id;
        $sugarId = $materials['sugar'] ?? DB::table('materials')->where('code', 'MAT_SUGAR')->first()?->id;
        $eggsId = $materials['eggs'] ?? DB::table('materials')->where('code', 'MAT_EGGS')->first()?->id;
        $butterId = $materials['butter'] ?? DB::table('materials')->where('code', 'MAT_BUTTER')->first()?->id;
        $milkId = $materials['milk'] ?? DB::table('materials')->where('code', 'MAT_MILK')->first()?->id;
        $saltId = $materials['salt'] ?? DB::table('materials')->where('code', 'MAT_SALT')->first()?->id;
        $yeastId = $materials['yeast'] ?? DB::table('materials')->where('code', 'MAT_YEAST')->first()?->id;
        $vanillaId = $materials['vanilla_extract'] ?? DB::table('materials')->where('code', 'MAT_VANILLA')->first()?->id;
        $chocoId = $materials['chocolate_powder'] ?? DB::table('materials')->where('code', 'MAT_CHOCO')->first()?->id;
        $bakingPowderId = $materials['baking_powder'] ?? DB::table('materials')->where('code', 'MAT_BAKING_POW')->first()?->id;
        $paperBagId = $materials['paper_bag'] ?? DB::table('materials')->where('code', 'PKG_PAPER_BAG')->first()?->id;
        $plasticWrapId = $materials['plastic_wrap'] ?? DB::table('materials')->where('code', 'PKG_PLASTIC_WRAP')->first()?->id;

        $now = Carbon::now();
        $kgUnitId = DB::table('units')->where('code', 'kg')->first()?->id;
        $gUnitId = DB::table('units')->where('code', 'g')->first()?->id;
        $pcsUnitId = DB::table('units')->where('code', 'pcs')->first()?->id;
        $lUnitId = DB::table('units')->where('code', 'l')->first()?->id;

        if (!$breadBomId || !$cakeBomId || !$croissantBomId || !$flourId) {
            $this->command->error('Required BOMs or materials not found. Make sure earlier seeders have run.');
            return;
        }

        $bomItems = [
            // ===== BREAD LOAF (500g) =====
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $breadBomId,
                'material_id' => (string) $flourId,
                'sub_product_id' => null,
                'quantity' => 400,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 2.5,
                'line_no' => 1,
                'metadata' => json_encode(['description' => 'Main ingredient']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $breadBomId,
                'material_id' => (string) $butterId,
                'sub_product_id' => null,
                'quantity' => 0.020,
                'unit_id' => (string) $kgUnitId,
                'wastage_percent' => 1.0,
                'line_no' => 2,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $breadBomId,
                'material_id' => (string) $saltId,
                'sub_product_id' => null,
                'quantity' => 10,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 0.5,
                'line_no' => 3,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $breadBomId,
                'material_id' => (string) $yeastId,
                'sub_product_id' => null,
                'quantity' => 5,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 0,
                'line_no' => 4,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $breadBomId,
                'material_id' => (string) $paperBagId,
                'sub_product_id' => null,
                'quantity' => 1,
                'unit_id' => (string) $pcsUnitId,
                'wastage_percent' => 0,
                'line_no' => 5,
                'metadata' => json_encode(['description' => 'Packaging']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],

            // ===== SPONGE CAKE (800g) =====
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $flourId,
                'sub_product_id' => null,
                'quantity' => 200,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 2.0,
                'line_no' => 1,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $sugarId,
                'sub_product_id' => null,
                'quantity' => 250,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 1.5,
                'line_no' => 2,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $eggsId,
                'sub_product_id' => null,
                'quantity' => 4,
                'unit_id' => (string) $pcsUnitId,
                'wastage_percent' => 5.0,
                'line_no' => 3,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $butterId,
                'sub_product_id' => null,
                'quantity' => 0.150,
                'unit_id' => (string) $kgUnitId,
                'wastage_percent' => 1.0,
                'line_no' => 4,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $milkId,
                'sub_product_id' => null,
                'quantity' => 0.100,
                'unit_id' => (string) $lUnitId,
                'wastage_percent' => 2.0,
                'line_no' => 5,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $vanillaId,
                'sub_product_id' => null,
                'quantity' => 5,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 0,
                'line_no' => 6,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $bakingPowderId,
                'sub_product_id' => null,
                'quantity' => 8,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 0,
                'line_no' => 7,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $cakeBomId,
                'material_id' => (string) $plasticWrapId,
                'sub_product_id' => null,
                'quantity' => 50,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 3.0,
                'line_no' => 8,
                'metadata' => json_encode(['description' => 'Packaging']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],

            // ===== CROISSANT (75g) =====
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $croissantBomId,
                'material_id' => (string) $flourId,
                'sub_product_id' => null,
                'quantity' => 45,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 3.0,
                'line_no' => 1,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $croissantBomId,
                'material_id' => (string) $butterId,
                'sub_product_id' => null,
                'quantity' => 0.030,
                'unit_id' => (string) $kgUnitId,
                'wastage_percent' => 2.0,
                'line_no' => 2,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $croissantBomId,
                'material_id' => (string) $sugarId,
                'sub_product_id' => null,
                'quantity' => 8,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 1.0,
                'line_no' => 3,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $croissantBomId,
                'material_id' => (string) $saltId,
                'sub_product_id' => null,
                'quantity' => 0.5,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 0,
                'line_no' => 4,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $croissantBomId,
                'material_id' => (string) $yeastId,
                'sub_product_id' => null,
                'quantity' => 0.3,
                'unit_id' => (string) $gUnitId,
                'wastage_percent' => 0,
                'line_no' => 5,
                'metadata' => json_encode([]),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'bom_id' => (string) $croissantBomId,
                'material_id' => (string) $paperBagId,
                'sub_product_id' => null,
                'quantity' => 1,
                'unit_id' => (string) $pcsUnitId,
                'wastage_percent' => 0,
                'line_no' => 6,
                'metadata' => json_encode(['description' => 'Packaging - per unit']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('bom_items')->insert($bomItems);

        $this->command->info('✅ BomItemSeeder completed: '.count($bomItems).' BOM items created.');
    }
}