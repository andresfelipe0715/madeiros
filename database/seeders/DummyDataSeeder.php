<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Material;
use App\Models\SpecialService;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed 60 Clients
        for ($i = 1; $i <= 60; $i++) {
            Client::create([
                'name' => "Cliente de Prueba $i",
                'document' => str_pad($i, 10, '0', STR_PAD_LEFT),
                'phone' => '300'.str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);
        }

        // Seed 60 Materials
        for ($i = 1; $i <= 60; $i++) {
            Material::create([
                'name' => "Material Pro $i",
                'stock_quantity' => rand(100, 500),
                'reserved_quantity' => 0,
            ]);
        }

        // Seed 60 Special Services
        for ($i = 1; $i <= 60; $i++) {
            SpecialService::create([
                'name' => "Servicio Elite $i",
                'active' => true,
            ]);
        }
    }
}
