<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SpecialServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            'Canto Delgado',
            'Canto Grueso',
            'Perforación Bisagra',
            'Corte Especial',
            'Ranurado',
            'Armado de Mueble',
            'Instalación',
            'Transporte',
        ];

        foreach ($services as $service) {
            \App\Models\SpecialService::create(['name' => $service]);
        }
    }
}
