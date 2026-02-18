<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data Migration
        $this->migratePerformancePermissions();

        // 2. Drop the column from role_visibility_permissions
        Schema::table('role_visibility_permissions', function (Blueprint $table) {
            $table->dropColumn('can_view_performance');
        });
    }

    protected function migratePerformancePermissions(): void
    {
        $now = now();
        $visibilityPermissions = DB::table('role_visibility_permissions')->get();

        foreach ($visibilityPermissions as $perm) {
            if ($perm->can_view_performance) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $perm->role_id, 'resource_type' => 'performance'],
                    [
                        'can_view' => true,
                        'can_create' => false,
                        'can_edit' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_visibility_permissions', function (Blueprint $table) {
            $table->boolean('can_view_performance')->default(false);
        });

        // Optional: Revert data (not strictly necessary for legacy cleanup but good practice)
        $perms = DB::table('role_permissions')->where('resource_type', 'performance')->get();
        foreach ($perms as $perm) {
            if ($perm->can_view) {
                DB::table('role_visibility_permissions')
                    ->where('role_id', $perm->role_id)
                    ->update(['can_view_performance' => true]);
            }
        }

        DB::table('role_permissions')->where('resource_type', 'performance')->delete();
    }
};
