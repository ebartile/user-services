<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title'       => $this->faker->words(3, true),
            'label'       => $this->faker->word(),
            'description' => $this->faker->paragraph(),
            'instruction' => $this->faker->paragraph(),
            'value'       => $this->faker->numberBetween(10, 100),
            'currency'    => 'USD',
        ];
    }
}
