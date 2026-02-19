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
        Schema::table('role_client_permissions', function (Blueprint $table) {
            $table->boolean('can_edit')->default(false)->after('can_create');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_client_permissions')) {
            Schema::table('role_client_permissions', function (Blueprint $table) {
                $table->dropColumn('can_edit');
            });
        }
    }
};
