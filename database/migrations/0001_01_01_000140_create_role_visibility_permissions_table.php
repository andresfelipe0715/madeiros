<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_visibility_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
            $table->boolean('can_view_files')->default(true);
            $table->boolean('can_view_order_file')->default(true);
            $table->boolean('can_view_machine_file')->default(true);
            $table->boolean('can_view_performance')->default(false); // Manually added as it was in schema dump but missing in backup migrations
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_visibility_permissions');
    }
};
