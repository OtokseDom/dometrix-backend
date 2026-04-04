<?php

namespace Database\Factories;

use App\Domain\Currencies\Models\Currencies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CurrenciesFactory extends Factory
{
    protected $model = Currencies::class;

    public function definition(): array
    {
        $codes = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'INR', 'MXN', 'ZAR'];

        return [
            'id' => Str::uuid(),
            'code' => $this->faker->unique()->randomElement($codes),
            'name' => $this->faker->country(),
            'metadata' => [],
        ];
    }
}
