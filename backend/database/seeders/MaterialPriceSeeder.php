<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MaterialPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        $userId = DB::table('users')->first()?->id;

        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping MaterialPriceSeeder.');
            return;
        }

        // Get materials
        $materials = cache()->get('seeder_materials') ?? [];
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
        $cardboxId = $materials['cardboard_box'] ?? DB::table('materials')->where('code', 'PKG_CARDBOX')->first()?->id;

        if (!$flourId) {
            $this->command->error('Required materials not found. Make sure MaterialSeeder has run.');
            return;
        }

        $now = Carbon::now();
        $effectiveDate = '2026-03-15';

        $materialPrices = [
            // Raw Materials - per kg
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $flourId,
                'price' => 1.2500,
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $sugarId,
                'price' => 1.5000,
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $eggsId,
                'price' => 0.3500,  // per piece
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $butterId,
                'price' => 4.7500,  // per kg
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $milkId,
                'price' => 0.8500,  // per liter
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $saltId,
                'price' => 0.0150,  // per gram (very cheap)
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $yeastId,
                'price' => 0.0850,  // per gram
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $vanillaId,
                'price' => 0.1750,  // per gram
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $chocoId,
                'price' => 2.2500,  // per kg
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $bakingPowderId,
                'price' => 0.0350,  // per gram
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            // Packaging Materials
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $paperBagId,
                'price' => 0.1250,  // per piece
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $plasticWrapId,
                'price' => 0.0075,  // per gram
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'material_id' => (string) $cardboxId,
                'price' => 0.2000,  // per piece
                'effective_date' => $effectiveDate,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('material_prices')->insert($materialPrices);

        $this->command->info('✅ MaterialPriceSeeder completed: ' . count($materialPrices) . ' material prices created.');
    }
}
