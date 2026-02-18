<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'invoice_number' => 'FAC-' . $this->faker->unique()->numberBetween(1000, 9999),
            'material' => $this->faker->word(),
            'lleva_herrajeria' => $this->faker->boolean(),
            'lleva_manual_armado' => $this->faker->boolean(),
        ];
    }
}
