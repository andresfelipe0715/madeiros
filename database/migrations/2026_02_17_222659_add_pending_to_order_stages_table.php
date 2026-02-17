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
        Schema::table('order_stages', function (Blueprint $table) {
            $table->boolean('is_pending')->default(false)->after('completed_at');
            $table->string('pending_reason', 250)->nullable()->after('is_pending');
            $table->foreignId('pending_marked_by')->nullable()->after('pending_reason')->constrained('users');
            $table->timestamp('pending_marked_at')->nullable()->after('pending_marked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_stages', function (Blueprint $table) {
            //
        });
    }
};
