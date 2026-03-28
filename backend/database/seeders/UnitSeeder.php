<?php

namespace Database\Seeders;

use App\Domain\Units\Models\Units;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Units::insert([
            // ===== SYSTEM MASTER UNITS =====
            // Quantity

            [
                'id' => (string) Str::uuid(), 'code' => 'pcs', 'name' => 'piece', 'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(), 'code' => 'dozen', 'name' => 'dozen', 'type' => 'quantity',
                'metadata' => null
            ],
            ['id' => (string) Str::uuid(), 'code' => 'box', 'name' => 'box', 'type' => 'quantity', 'metadata' => null],

            // Weight
            [
                'id' => (string) Str::uuid(), 'code' => 'kg', 'name' => 'kilogram', 'type' => 'weight',
                'metadata' => null
            ],
            ['id' => (string) Str::uuid(), 'code' => 'g', 'name' => 'gram', 'type' => 'weight', 'metadata' => null],

            // Volume
            ['id' => (string) Str::uuid(), 'code' => 'l', 'name' => 'liter', 'type' => 'volume', 'metadata' => null],
            [
                'id' => (string) Str::uuid(), 'code' => 'ml', 'name' => 'milliliter', 'type' => 'volume',
                'metadata' => null
            ],

            // Length
            ['id' => (string) Str::uuid(), 'code' => 'm', 'name' => 'meter', 'type' => 'length', 'metadata' => null],
            [
                'id' => (string) Str::uuid(), 'code' => 'cm', 'name' => 'centimeter', 'type' => 'length',
                'metadata' => null
            ],

            // ===== BUSINESS / OPTIONAL UNITS =====
            [
                'id' => (string) Str::uuid(), 'code' => 'crate', 'name' => 'crate', 'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(), 'code' => 'bundle', 'name' => 'bundle', 'type' => 'quantity',
                'metadata' => null
            ],
            [
                'id' => (string) Str::uuid(), 'code' => 'pack', 'name' => 'pack', 'type' => 'quantity',
                'metadata' => null
            ],
            ['id' => (string) Str::uuid(), 'code' => 'set', 'name' => 'set', 'type' => 'quantity', 'metadata' => null],
        ]);
    }
}
