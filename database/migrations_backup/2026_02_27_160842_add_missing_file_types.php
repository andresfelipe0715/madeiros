<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $types = ['Orden', 'Evidencia', 'Proyecto', 'Máquina'];

        foreach ($types as $type) {
            \App\Models\FileType::firstOrCreate(['name' => $type]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\FileType::whereIn('name', ['Evidencia', 'Proyecto', 'Máquina'])->delete();
    }
};
