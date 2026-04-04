<?php

namespace Database\Factories;

use App\Domain\Manufacturing\Models\Bom;
use App\Domain\Manufacturing\Models\Product;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BomFactory extends Factory
{
    protected $model = Bom::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'product_id' => Product::factory(),
            'version' => 1,
            'is_active' => false,
            'metadata' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }
}
