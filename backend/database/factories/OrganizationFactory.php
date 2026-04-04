<?php

namespace Database\Factories;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'code' => strtoupper(Str::random(6)),
            'name' => $this->faker->company(),
            'metadata' => [],
        ];
    }
}
