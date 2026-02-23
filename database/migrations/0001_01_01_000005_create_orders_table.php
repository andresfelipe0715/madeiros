<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('material', 255);
            $table->string('notes', 300)->nullable();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->boolean('lleva_herrajeria')->default(false);
            $table->boolean('lleva_manual_armado')->default(false);
            $table->timestamp('herrajeria_delivered_at')->nullable();
            $table->unsignedBigInteger('herrajeria_delivered_by')->nullable();
            $table->timestamp('manual_armado_delivered_at')->nullable();
            $table->unsignedBigInteger('manual_armado_delivered_by')->nullable();

            $table->foreign('herrajeria_delivered_by')->references('id')->on('users');
            $table->foreign('manual_armado_delivered_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
