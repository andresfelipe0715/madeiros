<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->integer('default_sequence')->default(0);
            $table->boolean('can_remit')->default(true);
            $table->boolean('is_delivery_stage')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
