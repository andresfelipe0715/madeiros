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
            \App\Models\RoleOrderPermission::updateOrCreate(
                ['role_id' => $adminRole->id],
                ['can_view' => true, 'can_edit' => true, 'can_create' => true]
            );
        }
    }
}
