<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$projectId = 1;
$rabs = App\Models\Rab::where('project_id', $projectId)
    ->orderBy('id')
    ->get(['id','name','status','created_at']);

echo "RABS project {$projectId}:" . PHP_EOL;
foreach ($rabs as $r) {
    echo "- id={$r->id} status={$r->status} name={$r->name}" . PHP_EOL;
}

$latestApproved = App\Models\Project::find($projectId)?->latestApprovedRab()->first();
if ($latestApproved) {
    echo "latestApprovedRab.id={$latestApproved->id} status={$latestApproved->status}" . PHP_EOL;
} else {
    echo "latestApprovedRab.id=NULL" . PHP_EOL;
}
