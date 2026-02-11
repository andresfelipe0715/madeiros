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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('notes', 300)->nullable()->change();
        });

        Schema::table('order_stages', function (Blueprint $table) {
            $table->string('notes', 300)->nullable()->change();
            $table->string('remit_reason', 300)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
        });

        Schema::table('order_stages', function (Blueprint $table) {
            $table->text('notes')->nullable()->change();
            $table->text('remit_reason')->nullable()->change();
        });
    }
};
