<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Organization\Models\Organization;
use App\Domain\Warehouses\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InventoryBatchFactory extends Factory
{
    protected $model = InventoryBatch::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'material_id' => Material::factory(),
            'warehouse_id' => Warehouse::factory(),
            'batch_number' => strtoupper($this->faker->unique()->bothify('BATCH-#####')),
            'manufactured_date' => now()->subDays(30)->toDateString(),
            'received_date' => now()->toDateString(),
            'expiry_date' => now()->addYears(1)->toDateString(),
            'received_qty' => 100,
            'remaining_qty' => 100,
            'unit_cost' => 50.00,
            'status' => 'ACTIVE',
            'metadata' => [],
        ];
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expiry_date' => now()->subDays(1)->toDateString(),
            'status' => 'EXPIRED',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'remaining_qty' => 0,
            'status' => 'CLOSED',
        ]);
    }
}
