<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Seeding order is critical to prevent foreign key violations.
     * Base seeders are called first, then dependent seeders.
     */
    public function run(): void
    {
        $this->call([
            // ===== BASE SEEDERS =====
            OrganizationSeeder::class,
            UserSeeder::class,
            RoleSeeder::class,
            OrganizationUserSeeder::class,
            UnitSeeder::class,
            CurrencySeeder::class,

            // ===== ERP MODULE SEEDERS =====
            // Foundational tables
            CategorySeeder::class,
            TaxSeeder::class,
            WarehouseSeeder::class,

            // Materials and Products
            MaterialSeeder::class,
            ProductSeeder::class,

            // Bill of Materials
            BomSeeder::class,
            BomItemSeeder::class,

            // Pricing
            MaterialPriceSeeder::class,
        ]);
    }
}
