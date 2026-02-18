<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class VisibilityPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = \App\Models\Role::all();

        foreach ($roles as $role) {
            \App\Models\RoleVisibilityPermission::firstOrCreate(
                ['role_id' => $role->id],
                [
                    'can_view_files' => true,
                    'can_view_order_file' => true,
                    'can_view_machine_file' => true,
                    'can_view_notes' => true,
                    'can_view_remit_history' => true,
                    'can_view_pending_reason' => true,
                    'can_view_performance' => false,
                ]
            );
        }
    }
}
