<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "BOOT_OK\n";
echo 'projects=' . \App\Models\Project::count() . "\n";
echo 'rab_breakdowns=' . \App\Models\RabBreakdown::count() . "\n";

$rows = \App\Models\RabBreakdown::query()
    ->orderBy('project_id')
    ->orderBy('order_number')
    ->limit(20)
    ->get();

foreach ($rows as $r) {
    echo $r->id . '|p' . $r->project_id . '|' . $r->rab_breakdown_code . '|' . $r->name . "\n";
}

