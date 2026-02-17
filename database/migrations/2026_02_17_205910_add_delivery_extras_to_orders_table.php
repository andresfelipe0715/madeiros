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
            $table->timestamp('herrajeria_delivered_at')->nullable();
            $table->unsignedBigInteger('herrajeria_delivered_by')->nullable();
            $table->timestamp('manual_armado_delivered_at')->nullable();
            $table->unsignedBigInteger('manual_armado_delivered_by')->nullable();

            $table->foreign('herrajeria_delivered_by')->references('id')->on('users');
            $table->foreign('manual_armado_delivered_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['herrajeria_delivered_by']);
            $table->dropForeign(['manual_armado_delivered_by']);
            $table->dropColumn([
                'herrajeria_delivered_at',
                'herrajeria_delivered_by',
                'manual_armado_delivered_at',
                'manual_armado_delivered_by',
            ]);
        });
    }
};
