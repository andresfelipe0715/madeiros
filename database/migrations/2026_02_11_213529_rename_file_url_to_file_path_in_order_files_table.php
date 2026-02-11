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
        Schema::table('order_files', function (Blueprint $table) {
            $table->renameColumn('file_url', 'file_path');
        });

        // Normalize existing data if any
        \Illuminate\Support\Facades\DB::table('order_files')->get()->each(function ($file) {
            $path = $file->file_path;
            $prefix = \Illuminate\Support\Facades\Storage::disk('public')->url('');

            if (str_starts_with($path, $prefix)) {
                $path = str_replace($prefix, '', $path);
                $path = ltrim($path, '/');
                \Illuminate\Support\Facades\DB::table('order_files')
                    ->where('id', $file->id)
                    ->update(['file_path' => $path]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->renameColumn('file_path', 'file_url');
        });
    }
};
