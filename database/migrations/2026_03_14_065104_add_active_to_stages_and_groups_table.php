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
        Schema::table('stage_groups', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('name');
        });

        Schema::table('stages', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('is_delivery_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stage_groups', function (Blueprint $table) {
            $table->dropColumn('active');
        });

        Schema::table('stages', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
