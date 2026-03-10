<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\StageGroup;
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
        // 0. Create Stage Groups
        $groups = ['Corte', 'Enchape', 'Servicios Especiales', 'Revisión', 'Entrega'];
        foreach ($groups as $groupName) {
            StageGroup::firstOrCreate(['name' => $groupName]);
        }

        // 1. Create Stages
        $stagesData = [
            'Corte 1' => ['sequence' => 10, 'group' => 'Corte'],
            'Corte 2' => ['sequence' => 15, 'group' => 'Corte'],
            'Enchape 1' => ['sequence' => 20, 'group' => 'Enchape'],
            'Enchape 2' => ['sequence' => 25, 'group' => 'Enchape'],
            'Servicios Especiales' => ['sequence' => 40, 'group' => 'Servicios Especiales'],
            'Revisión' => ['sequence' => 50, 'group' => 'Revisión'],
            'Entrega' => ['sequence' => 60, 'group' => 'Entrega'],
        ];

        $stages = [];
        foreach ($stagesData as $name => $data) {
            $group = StageGroup::where('name', $data['group'])->first();

            $stages[$name] = Stage::updateOrCreate(
                ['name' => $name],
                [
                    'default_sequence' => $data['sequence'],
                    'stage_group_id' => $group->id,
                    'is_delivery_stage' => ($name === 'Entrega'),
                ]
            );
        }

        // 2. Create Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->stages()->sync(Stage::pluck('id')->toArray());

        User::firstOrCreate(
            ['document' => 'admin_123'],
            [
                'name' => 'Admin Test',
                'role_id' => $adminRole->id,
                'password' => Hash::make('password'),
                'active' => true,
            ]
        );

        // 2.1 Create Bodega Role
        $bodegaRole = Role::firstOrCreate(['name' => 'Bodega']);
        User::firstOrCreate(
            ['document' => 'bodega_123'],
            [
                'name' => 'Bodega Test',
                'role_id' => $bodegaRole->id,
                'password' => Hash::make('password'),
                'active' => true,
            ]
        );

        // 3. Create a dedicated Role and User for each specific Stage
        foreach ($stages as $stageName => $stageModel) {
            $roleName = 'Empleado de ' . strtolower($stageName);
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Sync only this specific stage to this role
            $role->stages()->sync([$stageModel->id]);

            // Create a test user for this role
            $slug = strtolower(str_replace(' ', '_', $stageName));
            User::firstOrCreate(
                ['document' => $slug . '_123'],
                [
                    'name' => $roleName . ' Test',
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

        // 5. Create Materials (Moved from MaterialSeeder)
        $materialsData = [
            ['name' => 'Melamina Blanca 18mm', 'stock_quantity' => 100],
            ['name' => 'Melamina Roble 18mm', 'stock_quantity' => 50],
            ['name' => 'Canto PVC 0.5mm', 'stock_quantity' => 500],
        ];

        foreach ($materialsData as $material) {
            \App\Models\Material::updateOrCreate(
                ['name' => $material['name']],
                [
                    'stock_quantity' => $material['stock_quantity'],
                    'bodega_quantity' => 0,
                ]
            );
        }

        // 6. Create Special Services
        $this->call(SpecialServiceSeeder::class);

        // 5. Populate role_order_permissions
        // 5. Populate role_order_permissions and role_client_permissions
        // 5. Populate role_order_permissions and role_client_permissions for Admin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            // Grant access to ALL stages
            $adminRole->stages()->sync(Stage::pluck('id')->toArray());

            // Grant all Order permissions
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'orders'],
                ['can_view' => true, 'can_edit' => true, 'can_create' => true]
            );

            // Grant all Client permissions
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'clients'],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true]
            );

            // Grant Performance permission
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'performance'],
                ['can_view' => true, 'can_create' => false, 'can_edit' => false]
            );

            // Grant Users permission
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'users'],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true]
            );

            // Grant Materials permission
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'materials'],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true]
            );

            // Grant Special Services permission
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'special_services'],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true]
            );

            // Grant Full Visibility
            \App\Models\RoleVisibilityPermission::updateOrCreate(
                ['role_id' => $adminRole->id],
                ['can_view_files' => true, 'can_view_order_file' => true, 'can_view_machine_file' => true]
            );

            // Grant Bodega permission
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'bodega'],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true]
            );
        }

        $otherRoles = Role::where('name', '!=', 'Admin')->get();
        foreach ($otherRoles as $role) {
            // Default: View only for orders
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'orders'],
                ['can_view' => false, 'can_edit' => false, 'can_create' => false]
            );

            // Deny client permissions for others by default
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'clients'],
                ['can_view' => false, 'can_create' => false, 'can_edit' => false]
            );

            // Deny user permissions for others by default
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'users'],
                ['can_view' => false, 'can_edit' => false, 'can_create' => false]
            );

            // Deny performance permissions for others by default
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'performance'],
                ['can_view' => false, 'can_create' => false, 'can_edit' => false]
            );

            // Deny materials permissions for others by default
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'materials'],
                ['can_view' => false, 'can_create' => false, 'can_edit' => false]
            );

            // Deny special services permissions for others by default
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'special_services'],
                ['can_view' => false, 'can_create' => false, 'can_edit' => false]
            );

            // Default Visibility: All true
            \App\Models\RoleVisibilityPermission::updateOrCreate(
                ['role_id' => $role->id],
                ['can_view_files' => true, 'can_view_order_file' => true, 'can_view_machine_file' => true]
            );

            // Default Bodega permission: off for others
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'resource_type' => 'bodega'],
                ['can_view' => false, 'can_create' => false, 'can_edit' => false]
            );
        }

        // Specifically set Bodega role permissions
        $bodegaRole = Role::where('name', 'Bodega')->first();
        if ($bodegaRole) {
            RolePermission::updateOrCreate(
                ['role_id' => $bodegaRole->id, 'resource_type' => 'bodega'],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true]
            );
            RolePermission::updateOrCreate(
                ['role_id' => $bodegaRole->id, 'resource_type' => 'materials'],
                ['can_view' => true, 'can_create' => false, 'can_edit' => false]
            );
            RolePermission::updateOrCreate(
                ['role_id' => $bodegaRole->id, 'resource_type' => 'orders'],
                ['can_view' => true, 'can_edit' => false, 'can_create' => false]
            );
        }

    }
}
