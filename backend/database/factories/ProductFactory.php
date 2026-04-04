<?php

namespace Database\Factories;

use App\Domain\Manufacturing\Models\Product;
use App\Domain\Organization\Models\Organization;
use App\Domain\Units\Models\Units;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('PRD-####')),
            'name' => $this->faker->word() . ' Product',
            'description' => $this->faker->sentence(),
            'unit_id' => Units::factory(),
            'metadata' => [],
        ];
    }
}
