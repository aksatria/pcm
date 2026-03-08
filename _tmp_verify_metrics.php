<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sources = App\Models\RabBreakdownBudgetSource::whereHas('rabBreakdownItem.rabBreakdown', function ($q) {
    $q->where('project_id', 1);
})->where(function ($q) {
    $q->whereNotNull('qty')
      ->orWhereNotNull('qty_beli')
      ->orWhereNotNull('jumlah')
      ->orWhereNotNull('m2_peng');
})->count();

$items = App\Models\RabBreakdownItem::whereHas('rabBreakdown', function ($q) {
    $q->where('project_id', 1);
})->where(function ($q) {
    $q->whereNotNull('qty')
      ->orWhereNotNull('qty_beli')
      ->orWhereNotNull('jumlah')
      ->orWhereNotNull('m2_peng');
})->count();

echo 'sources_non_null=' . $sources . PHP_EOL;
echo 'items_non_null=' . $items . PHP_EOL;
