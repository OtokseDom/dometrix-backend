<?php

namespace Database\Factories;

use App\Domain\Manufacturing\Models\BomItem;
use App\Domain\Manufacturing\Models\Bom;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Manufacturing\Models\Product;
use App\Domain\Organization\Models\Organization;
use App\Domain\Units\Models\Units;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BomItemFactory extends Factory
{
    protected $model = BomItem::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'bom_id' => Bom::factory(),
            'material_id' => Material::factory(),
            'sub_product_id' => null,
            'quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit_id' => Units::factory(),
            'wastage_percent' => $this->faker->randomFloat(2, 0, 10),
            'line_no' => $this->faker->numberBetween(1, 50),
            'metadata' => [],
        ];
    }

    public function withSubProduct(): static
    {
        return $this->state(fn(array $attributes) => [
            'material_id' => null,
            'sub_product_id' => Product::factory(),
        ]);
    }
}
