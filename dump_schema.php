<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['stages', 'role_visibility_permissions', 'orders', 'order_stages'];
$output = [];
foreach ($tables as $table) {
    try {
        $columns = DB::select("PRAGMA table_info($table)");
        if (empty($columns)) {
            $columns = DB::select("SHOW COLUMNS FROM $table");
        }
        $output[$table] = $columns;
    } catch (\Exception $e) {
        $output[$table] = $e->getMessage();
    }
}
file_put_contents('schema_dump.json', json_encode($output, JSON_PRETTY_PRINT));
echo "Done.\n";
