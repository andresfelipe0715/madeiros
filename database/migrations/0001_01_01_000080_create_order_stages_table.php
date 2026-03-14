<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('stage_id')->constrained('stages');
            $table->string('notes', 300)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->integer('sequence');
            $table->boolean('is_pending')->default(false);
            $table->string('pending_reason', 250)->nullable();
            $table->foreignId('pending_marked_by')->nullable()->constrained('users');
            $table->timestamp('pending_marked_at')->nullable();

            $table->unique(['order_id', 'stage_id']);
            $table->unique(['order_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stages');
    }
};
