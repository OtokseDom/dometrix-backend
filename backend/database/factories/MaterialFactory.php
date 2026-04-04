<?php

namespace Database\Factories;

use App\Domain\Manufacturing\Models\Material;
use App\Domain\Organization\Models\Organization;
use App\Domain\Units\Models\Units;
use App\Domain\Categories\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('MAT-####')),
            'name' => $this->faker->word() . ' Material',
            'category_id' => Category::factory(),
            'unit_id' => Units::factory(),
            'metadata' => [],
        ];
    }
}
