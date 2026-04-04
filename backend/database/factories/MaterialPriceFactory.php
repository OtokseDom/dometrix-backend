<?php

namespace Database\Factories;

use App\Domain\Manufacturing\Models\MaterialPrice;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MaterialPriceFactory extends Factory
{
    protected $model = MaterialPrice::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'material_id' => Material::factory(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'effective_date' => now()->toDateString(),
            'created_by' => \App\Domain\User\Models\User::factory(),
        ];
    }

    public function futureDate(): static
    {
        return $this->state(fn(array $attributes) => [
            'effective_date' => now()->addDays(30)->toDateString(),
        ]);
    }

    public function pastDate(): static
    {
        return $this->state(fn(array $attributes) => [
            'effective_date' => now()->subDays(30)->toDateString(),
        ]);
    }
}
