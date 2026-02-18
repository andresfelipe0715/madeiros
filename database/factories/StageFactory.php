<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

class StageFactory extends Factory
{
    protected $model = Stage::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'default_sequence' => $this->faker->numberBetween(1, 10),
            'is_delivery_stage' => false,
        ];
    }
}
