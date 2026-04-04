<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Organization\Models\Organization;
use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Units\Models\Units;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $movementTypes = ['STOCK_IN', 'STOCK_OUT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'TRANSFER_IN', 'TRANSFER_OUT'];
        $selectedType = $this->faker->randomElement($movementTypes);
        $direction = str_contains($selectedType, 'IN') || str_contains($selectedType, 'ADJUSTMENT_IN') ? 'IN' : 'OUT';

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'warehouse_id' => Warehouse::factory(),
            'material_id' => Material::factory(),
            'batch_id' => InventoryBatch::factory(),
            'reference_type' => 'ORDER',
            'reference_id' => Str::uuid(),
            'movement_type' => $selectedType,
            'quantity' => $this->faker->randomFloat(2, 1, 50),
            'unit_of_measure_id' => Units::factory(),
            'unit_cost' => $this->faker->randomFloat(2, 10, 500),
            'total_cost' => 0, // calculated
            'running_balance' => $this->faker->randomFloat(2, 0, 1000),
            'direction' => $direction,
            'performed_by' => User::factory(),
            'remarks' => $this->faker->sentence(),
            'metadata' => [],
        ];
    }

    public function stockIn(): static
    {
        return $this->state(fn(array $attributes) => [
            'movement_type' => 'STOCK_IN',
            'direction' => 'IN',
        ]);
    }

    public function stockOut(): static
    {
        return $this->state(fn(array $attributes) => [
            'movement_type' => 'STOCK_OUT',
            'direction' => 'OUT',
        ]);
    }
}
