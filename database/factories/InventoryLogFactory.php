<?php

namespace Database\Factories;

use App\Models\InventoryLog;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryLog>
 */
class InventoryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousStock = $this->faker->randomFloat(2, 50, 500);
        $adjustment = $this->faker->randomFloat(2, -50, 50);
        $newStock = $previousStock + $adjustment;

        return [
            'material_id' => Material::factory(),
            'user_id' => User::first() ?? User::factory(),
            'action' => $this->faker->randomElement(['bodega_entry', 'bodega_adjustment', 'adjustment']),
            'previous_stock_quantity' => $previousStock,
            'new_stock_quantity' => $newStock,
            'notes' => $this->faker->sentence(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
