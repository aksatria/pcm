<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codes = App\Models\RabBreakdownBudgetSource::query()
    ->select('master_kode')
    ->whereHas('rabBreakdownItem.rabBreakdown', function ($q) { $q->where('project_id', 1); })
    ->whereNotNull('master_kode')
    ->groupBy('master_kode')
    ->orderBy('master_kode')
    ->pluck('master_kode')
    ->all();

echo 'code_count=' . count($codes) . PHP_EOL;
echo implode(', ', array_slice($codes, 0, 200)) . PHP_EOL;
