<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryCostLayer;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Organization\Models\Organization;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Inventory\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InventoryCostLayerFactory extends Factory
{
    protected $model = InventoryCostLayer::class;

    public function definition(): array
    {
        $originalQty = $this->faker->randomFloat(2, 1, 500);
        $remainingQty = $this->faker->randomFloat(2, 0, $originalQty);

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'warehouse_id' => Warehouse::factory(),
            'material_id' => Material::factory(),
            'batch_id' => InventoryBatch::factory(),
            'source_movement_id' => InventoryMovement::factory(),
            'original_qty' => $originalQty,
            'remaining_qty' => $remainingQty,
            'unit_cost' => $this->faker->randomFloat(2, 10, 500),
            'received_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ];
    }
}
