<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Organization\Models\Organization;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Inventory\Models\InventoryBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InventoryBalanceFactory extends Factory
{
    protected $model = InventoryBalance::class;

    public function definition(): array
    {
        $onHandQty = $this->faker->randomFloat(2, 0, 1000);
        $reservedQty = $this->faker->randomFloat(2, 0, $onHandQty);

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'warehouse_id' => Warehouse::factory(),
            'material_id' => Material::factory(),
            'batch_id' => InventoryBatch::factory(),
            'on_hand_qty' => $onHandQty,
            'reserved_qty' => $reservedQty,
            'available_qty' => $onHandQty - $reservedQty,
            'average_cost' => $this->faker->randomFloat(2, 10, 500),
            'updated_at' => now(),
        ];
    }
}
