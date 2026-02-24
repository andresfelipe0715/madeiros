<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().' '.$this->faker->numberBetween(10, 50).' mm',
            'stock_quantity' => $this->faker->randomFloat(2, 100, 500),
            'reserved_quantity' => 0,
        ];
    }
}
