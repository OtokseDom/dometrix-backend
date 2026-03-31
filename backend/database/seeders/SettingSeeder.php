<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch all organizations
        $organizations = DB::table('organizations')->get();

        foreach ($organizations as $org) {
            // Fetch some defaults
            $baseCurrency = DB::table('currencies')->where('code', 'AED')->first();
            $defaultTax = DB::table('taxes')->where('name', 'VAT 5%')->first();
            $defaultWarehouse = DB::table('warehouses')->where('organization_id', $org->id)->first();

            DB::table('settings')->insert([
                'id' => Str::uuid(),
                'organization_id' => $org->id,
                'inventory_method' => 'fifo',
                'costing_method' => 'weighted_average',
                'allow_negative_stock' => false,
                'tax_inclusive_pricing' => false,
                'base_currency_id' => $baseCurrency->id ?? null,
                'default_tax_id' => $defaultTax->id ?? null,
                'default_warehouse_id' => $defaultWarehouse->id ?? null,
                'decimal_precision' => 4,
                'timezone' => 'Asia/Dubai',
                'metadata' => json_encode([
                    'production' => [
                        'auto_close_order' => true,
                        'default_shift' => 'morning'
                    ],
                    'sales' => [
                        'quotation_prefix' => 'QT',
                        'invoice_prefix' => 'INV'
                    ]
                ]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
