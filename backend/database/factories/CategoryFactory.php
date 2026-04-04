<?php

namespace Database\Factories;

use App\Domain\Categories\Models\Category;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $types = ['material', 'product', 'bom', 'other'];

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'code' => strtoupper($this->faker->unique()->word()),
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement($types),
            'parent_id' => null,
            'metadata' => [],
        ];
    }

    public function material(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'material',
        ]);
    }

    public function product(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'product',
        ]);
    }
}
