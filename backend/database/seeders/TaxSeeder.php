<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizationId = DB::table('organizations')->first()?->id;
        if (!$organizationId) {
            $this->command->warn('No organization found. Skipping TaxSeeder.');
            return;
        }

        $now = Carbon::now();

        $taxes = [
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'VAT_5',
                'name' => 'VAT 5%',
                'rate' => 5.00,
                'is_active' => true,
                'metadata' => json_encode(['description' => 'Standard VAT rate for taxable goods']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'VAT_10',
                'name' => 'VAT 10%',
                'rate' => 10.00,
                'is_active' => true,
                'metadata' => json_encode(['description' => 'Higher VAT rate for premium goods']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'id' => (string) Str::uuid(),
                'organization_id' => (string) $organizationId,
                'code' => 'VAT_ZERO',
                'name' => 'Zero-Rated',
                'rate' => 0.00,
                'is_active' => true,
                'metadata' => json_encode(['description' => 'Zero-rated for essential goods']),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ];

        DB::table('taxes')->insert($taxes);

        $this->command->info('✅ TaxSeeder completed: ' . count($taxes) . ' tax rates created.');
    }
}
