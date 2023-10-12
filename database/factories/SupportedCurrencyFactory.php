<?php

namespace Database\Factories;

use App\Models\SupportedCurrency;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportedCurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SupportedCurrency::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->currencyCode, // Generates a random currency code
            'name' => $this->faker->word,         // Generates a random name
            'default' => $this->faker->boolean,   // Generates a random boolean value
        ];
    }
}
