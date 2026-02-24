<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Material::updateOrCreate(['name' => 'Melamina Blanca 18mm'], ['stock_quantity' => 100]);
        \App\Models\Material::updateOrCreate(['name' => 'Melamina Roble 18mm'], ['stock_quantity' => 50]);
        \App\Models\Material::updateOrCreate(['name' => 'Canto PVC 0.5mm'], ['stock_quantity' => 500]);
    }
}
