<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping CategorySeeder.');
            return;
        }

        // Generate deterministic UUIDs for category IDs for reuse in other seeders
        $categories = [
            'raw_materials' => Str::uuid(),
            'packaging' => Str::uuid(),
            'finished_goods' => Str::uuid(),
            'semi_finished' => Str::uuid(),
        ];

        $now = Carbon::now();

        DB::table('categories')->insert([
            [
                'id' => (string) $categories['raw_materials'],
                'organization_id' => (string) $organizationId,
                'code' => 'RAW_MATERIALS',
                'name' => 'Raw Materials',
                'type' => 'material',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'All raw materials used in production']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $categories['packaging'],
                'organization_id' => (string) $organizationId,
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
                'id' => (string) $categories['finished_goods'],
                'organization_id' => (string) $organizationId,
                'code' => 'FINISHED_GOODS',
                'name' => 'Finished Goods',
                'type' => 'product',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'Final products ready for sale']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) $categories['semi_finished'],
                'organization_id' => (string) $organizationId,
                'code' => 'SEMI_FINISHED',
                'name' => 'Semi-Finished Products',
                'type' => 'product',
                'parent_id' => null,
                'metadata' => json_encode(['description' => 'Intermediate products in production']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);

        // Store category IDs in cache for other seeders
        cache()->put('seeder_categories', $categories, now()->addHour());

        $this->command->info('✅ CategorySeeder completed: ' . count($categories) . ' categories created.');
    }
}
