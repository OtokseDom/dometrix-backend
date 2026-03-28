<?php

namespace Database\Seeders;

use App\Domain\Currencies\Models\Currencies;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'id' => (string) Str::uuid(),
                'code' => 'USD',
                'name' => 'US Dollar',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'PHP',
                'name' => 'Philippine Peso',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(),
                'code' => 'AED',
                'name' => 'UAE Dirham',
                'metadata' => null
            ],
        ];

        Currencies::insert($currencies);
    }
}
