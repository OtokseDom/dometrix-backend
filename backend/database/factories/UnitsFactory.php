<?php

namespace Database\Factories;

use App\Domain\Units\Models\Units;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UnitsFactory extends Factory
{
    protected $model = Units::class;

    public function definition(): array
    {
        $unitTypes = ['length', 'weight', 'volume', 'quantity', 'time', 'area'];
        $codes = ['KG', 'LB', 'M', 'CM', 'L', 'ML', 'EA', 'BOX', 'ROLL', 'SHEET'];

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'code' => $this->faker->unique()->randomElement($codes),
            'name' => $this->faker->unique()->word(),
            'type' => $this->faker->randomElement($unitTypes),
            'metadata' => [],
        ];
    }
}
