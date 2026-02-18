<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_visibility_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->boolean('can_view_files')->default(true);
            $table->boolean('can_view_order_file')->default(true);
            $table->boolean('can_view_machine_file')->default(true);
            $table->boolean('can_view_notes')->default(true);
            $table->boolean('can_view_remit_history')->default(true);
            $table->boolean('can_view_pending_reason')->default(true);
            $table->boolean('can_view_performance')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_visibility_permissions');
    }
};
