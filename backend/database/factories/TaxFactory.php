<?php

namespace Database\Factories;

use App\Domain\Taxes\Models\Tax;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'code' => strtoupper($this->faker->unique()->word()),
            'name' => $this->faker->word(),
            'rate' => $this->faker->randomFloat(2, 0, 20),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
