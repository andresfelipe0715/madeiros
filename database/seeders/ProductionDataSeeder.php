<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Stage;
use App\Models\StageGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder is intended for production or staging environments.
     * It sets up the essential structure (Stages, Roles, Permissions)
     * without including sample transactional data or test users.
     */
    public function run(): void
    {
        // 0. Create Stage Groups
        $groups = ['Corte', 'Enchape', 'Servicios Especiales', 'Revisión', 'Entrega'];
        foreach ($groups as $groupName) {
            StageGroup::firstOrCreate(
                ['name' => $groupName],
                ['active' => true]
            );
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
                    'active' => true,
                ]
            );
        }

        // 2. Create Admin Role and Initial User
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->stages()->sync(Stage::pluck('id')->toArray());

        User::firstOrCreate(
            ['document' => 'admin_madeiros'],
            [
                'name' => 'Administrador',
                'role_id' => $adminRole->id,
                'password' => Hash::make('Madeiros2024*'), // Change this after first login
                'active' => true,
            ]
        );

        // 3. Create Bodega Role
        $bodegaRole = Role::firstOrCreate(['name' => 'Bodega']);

        // 4. Create Worker Roles (without test users)
        foreach ($stages as $stageName => $stageModel) {
            $roleName = 'Empleado de ' . strtolower($stageName);
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Sync only this specific stage to this role
            $role->stages()->sync([$stageModel->id]);
        }

        // 5. Create Essential Materials (Types only, 0 stock)
        $materialsData = [
            ['name' => 'Melamina Blanca 18mm'],
            ['name' => 'Melamina Roble 18mm'],
            ['name' => 'Canto PVC 0.5mm'],
        ];

        foreach ($materialsData as $material) {
            \App\Models\Material::updateOrCreate(
                ['name' => $material['name']],
                [
                    'stock_quantity' => 0,
                    'bodega_quantity' => 0,
                ]
            );
        }

        // 6. Create Default Special Services
        $this->call(SpecialServiceSeeder::class);

        // 7. Setup Detailed Permissions
        $this->setupPermissions($adminRole, $bodegaRole);
    }

    /**
     * Configure resource-level permissions for core roles.
     */
    protected function setupPermissions(Role $adminRole, Role $bodegaRole): void
    {
        // Admin Permissions - ALL ACCESS
        $resources = ['orders', 'clients', 'users', 'performance', 'materials', 'special_services', 'bodega'];
        foreach ($resources as $resource) {
            RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => $resource],
                ['can_view' => true, 'can_edit' => true, 'can_create' => true]
            );
        }

        \App\Models\RoleVisibilityPermission::updateOrCreate(
            ['role_id' => $adminRole->id],
            ['can_view_files' => true, 'can_view_order_file' => true, 'can_view_machine_file' => true]
        );

        // Bodega Permissions
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

        \App\Models\RoleVisibilityPermission::updateOrCreate(
            ['role_id' => $bodegaRole->id],
            ['can_view_files' => true, 'can_view_order_file' => true, 'can_view_machine_file' => true]
        );

        // Default Worker Permissions (Denied access to administrative modules)
        $workerRoles = Role::whereNotIn('name', ['Admin', 'Bodega'])->get();
        foreach ($workerRoles as $role) {
            foreach ($resources as $resource) {
                RolePermission::updateOrCreate(
                    ['role_id' => $role->id, 'resource_type' => $resource],
                    ['can_view' => false, 'can_edit' => false, 'can_create' => false]
                );
            }

            // Workers can view files (to see designs/instructions)
            \App\Models\RoleVisibilityPermission::updateOrCreate(
                ['role_id' => $role->id],
                ['can_view_files' => true, 'can_view_order_file' => true, 'can_view_machine_file' => true]
            );
        }
    }
}
