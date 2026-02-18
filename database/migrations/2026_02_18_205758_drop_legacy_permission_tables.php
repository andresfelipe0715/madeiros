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
        Schema::dropIfExists('role_order_permissions');
        Schema::dropIfExists('role_client_permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback for dropping legacy tables after migration
    }
};
