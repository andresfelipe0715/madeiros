<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdminOrderPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();
        if ($adminRole) {
            \App\Models\RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'orders'],
                ['can_view' => true, 'can_edit' => true, 'can_create' => true]
            );

            \App\Models\RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'materials'],
                ['can_view' => true, 'can_edit' => true, 'can_create' => true]
            );

            \App\Models\RolePermission::updateOrCreate(
                ['role_id' => $adminRole->id, 'resource_type' => 'special_services'],
                ['can_view' => true, 'can_edit' => true, 'can_create' => true]
            );
        }
    }
}
