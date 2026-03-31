<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping BomSeeder.');
            return;
        }

        $now = Carbon::now();

        // Get product IDs
        $products = cache()->get('seeder_products') ?? [];
        $breadLoafId = $products['bread_loaf'] ?? DB::table('products')
            ->where('code', 'PROD_BREAD')
            ->first()?->id;

        $spongeCakeId = $products['sponge_cake'] ?? DB::table('products')
            ->where('code', 'PROD_SPONGE')
            ->first()?->id;

        $croissantId = $products['croissant'] ?? DB::table('products')
            ->where('code', 'PROD_CROISSANT')
            ->first()?->id;

        if (!$breadLoafId || !$spongeCakeId || !$croissantId) {
            $this->command->error('Required products not found. Make sure ProductSeeder has run.');
            return;
        }

        // Store BOM IDs for use in BomItemSeeder
        $boms = [
            'bread_loaf_v1' => Str::uuid(),
            'sponge_cake_v1' => Str::uuid(),
            'croissant_v1' => Str::uuid(),
        ];

        $bomInserts = [
            [
                'id' => (string) $boms['bread_loaf_v1'],
                'organization_id' => (string) $organizationId,
                'product_id' => (string) $breadLoafId,
                'version' => '1.0',
                'is_active' => true,
                'metadata' => json_encode(['process' => 'standard baking', 'baking_temp' => '200°C', 'baking_time' => '35 minutes']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $boms['sponge_cake_v1'],
                'organization_id' => (string) $organizationId,
                'product_id' => (string) $spongeCakeId,
                'version' => '1.0',
                'is_active' => true,
                'metadata' => json_encode(['process' => 'creaming method', 'baking_temp' => '170°C', 'baking_time' => '45 minutes']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $boms['croissant_v1'],
                'organization_id' => (string) $organizationId,
                'product_id' => (string) $croissantId,
                'version' => '1.0',
                'is_active' => true,
                'metadata' => json_encode(['process' => 'lamination', 'baking_temp' => '190°C', 'baking_time' => '18 minutes']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('boms')->insert($bomInserts);

        // Store BOM IDs in cache for BomItemSeeder
        cache()->put('seeder_boms', $boms, now()->addHour());

        $this->command->info('✅ BomSeeder completed: ' . count($boms) . ' BOMs created.');
    }
}
