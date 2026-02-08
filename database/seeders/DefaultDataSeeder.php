<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Stages
        $stagesData = [
            'Corte',
            'Enchape',
            'Servicios Especiales',
            'Revisión',
            'Entrega',
        ];

        $stages = [];
        foreach ($stagesData as $name) {
            $stages[$name] = Stage::firstOrCreate(['name' => $name]);
        }

        // 2. Create Roles and link to Stages
        $rolesData = [
            'Admin' => ['Corte', 'Enchape', 'Servicios Especiales', 'Revisión', 'Entrega'],
            'Empleado de corte' => ['Corte'],
            'Empleado de enchape' => ['Enchape'],
            'Empleado de servicios especiales' => ['Servicios Especiales'],
            'Empleado de revisión' => ['Revisión', 'Servicios Especiales'],
            'Empleado de entrega' => ['Entrega'],
        ];

        $roleCodes = [
            'Admin' => 'admin',
            'Empleado de corte' => 'corte',
            'Empleado de enchape' => 'enchape',
            'Empleado de servicios especiales' => 'especiales',
            'Empleado de revisión' => 'revision',
            'Empleado de entrega' => 'entrega',
        ];

        foreach ($rolesData as $roleName => $assignedStages) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Sync stages
            $stageIds = [];
            foreach ($assignedStages as $stageName) {
                if (isset($stages[$stageName])) {
                    $stageIds[] = $stages[$stageName]->id;
                }
            }
            $role->stages()->sync($stageIds);

            // 3. Create one test user per role
            // Using document as a unique identifier for testing
            $document = $roleCodes[$roleName].'_123';

            User::firstOrCreate(
                ['document' => $document],
                [
                    'name' => $roleName.' Test',
                    'role_id' => $role->id,
                    'password' => Hash::make('password'),
                    'active' => true,
                ]
            );
        }

        // 4. Create sample clients
        $clientsData = [
            ['name' => 'Cliente Uno', 'document' => '123456789', 'phone' => '3001112222'],
            ['name' => 'Cliente Dos', 'document' => '987654321', 'phone' => '3003334444'],
        ];

        foreach ($clientsData as $client) {
            Client::firstOrCreate(
                ['document' => $client['document']],
                [
                    'name' => $client['name'],
                    'phone' => $client['phone'],
                ]
            );
        }
    }
}
