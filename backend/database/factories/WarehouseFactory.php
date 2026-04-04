<?php

namespace Database\Factories;

use App\Domain\Warehouses\Models\Warehouse;
use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $types = ['main', 'secondary', 'distribution', 'overflow'];

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('WH-####')),
            'name' => $this->faker->city() . ' Warehouse',
            'type' => $this->faker->randomElement($types),
            'location' => $this->faker->address(),
            'is_active' => true,
            'manager_user_id' => null,
            'metadata' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withManager(): static
    {
        return $this->state(fn(array $attributes) => [
            'manager_user_id' => User::factory(),
        ]);
    }
}
