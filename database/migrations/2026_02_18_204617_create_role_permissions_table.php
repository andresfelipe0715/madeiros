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
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type'); // e.g., 'orders', 'clients', 'stages'
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'resource_type']);
        });

        // Data Migration
        $this->migrateExistingPermissions();
    }

    protected function migrateExistingPermissions(): void
    {
        $now = now();

        // Migrate Order Permissions
        if (Schema::hasTable('role_order_permissions')) {
            $orderPermissions = DB::table('role_order_permissions')->get();
            foreach ($orderPermissions as $perm) {
                DB::table('role_permissions')->insert([
                    'role_id' => $perm->role_id,
                    'resource_type' => 'orders',
                    'can_view' => $perm->can_view,
                    'can_create' => $perm->can_create,
                    'can_edit' => $perm->can_edit,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Migrate Client Permissions
        if (Schema::hasTable('role_client_permissions')) {
            $clientPermissions = DB::table('role_client_permissions')->get();
            foreach ($clientPermissions as $perm) {
                DB::table('role_permissions')->insert([
                    'role_id' => $perm->role_id,
                    'resource_type' => 'clients',
                    'can_view' => $perm->can_view,
                    'can_create' => $perm->can_create,
                    'can_edit' => $perm->can_edit,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
