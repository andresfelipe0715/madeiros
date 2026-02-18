<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Tables:\n";
foreach (DB::select('SELECT name FROM sqlite_master WHERE type="table"') as $table) {
    echo '- '.$table->name."\n";
}

try {
    echo "Running migrate...\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();
    file_put_contents('debug_output.txt', $output);
    echo "Done. Check debug_output.txt\n";
} catch (\Exception $e) {
    $error = 'Error: '.$e->getMessage()."\n".$e->getTraceAsString();
    file_put_contents('debug_output.txt', $error);
    echo "Check debug_output.txt for error.\n";
}
