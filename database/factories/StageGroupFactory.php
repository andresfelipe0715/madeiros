<?php

namespace Database\Factories;

use App\Models\StageGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class StageGroupFactory extends Factory
{
    protected $model = StageGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
        ];
    }
}
