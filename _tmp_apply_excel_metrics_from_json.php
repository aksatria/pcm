<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

use App\Models\RabBreakdownBudgetSource;
use App\Models\RabBreakdownItem;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jsonPath = __DIR__ . '/_tmp_excel_metrics.json';
$projectId = 1;

if (!file_exists($jsonPath)) {
    echo "ERROR: JSON extract not found: {$jsonPath}\n";
    exit(1);
}

function toFloat($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        return (float) $value;
    }
    $raw = str_replace(['Rp', 'rp', ' ', '.'], '', (string) $value);
    $raw = str_replace(',', '.', $raw);
    if ($raw === '' || !is_numeric($raw)) {
        return null;
    }
    return (float) $raw;
}

$jsonRaw = file_get_contents($jsonPath);
$jsonRaw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $jsonRaw);
$rows = json_decode($jsonRaw, true);
if (!is_array($rows)) {
    echo "ERROR: invalid JSON data.\n";
    exit(1);
}

$entriesByKode = [];
foreach ($rows as $row) {
    $kode = trim((string) ($row['master_kode'] ?? ''));
    if ($kode === '') {
        continue;
    }
    $entriesByKode[$kode][] = [
        'row' => $row['row'] ?? null,
        'p' => toFloat($row['p'] ?? null),
        'l' => toFloat($row['l'] ?? null),
        't' => toFloat($row['t'] ?? null),
        'n' => toFloat($row['n'] ?? null),
        'n_tul_1' => toFloat($row['n_tul_1'] ?? null),
        'n_tul_2' => toFloat($row['n_tul_2'] ?? null),
        'jarak' => toFloat($row['jarak'] ?? null),
        'dia_1' => toFloat($row['dia_1'] ?? null),
        'dia_2' => toFloat($row['dia_2'] ?? null),
        'dia_3' => toFloat($row['dia_3'] ?? null),
        'berat_1' => toFloat($row['berat_1'] ?? null),
        'berat_2' => toFloat($row['berat_2'] ?? null),
        'm2_peng' => toFloat($row['m2_peng'] ?? null),
        'qty' => toFloat($row['qty'] ?? null),
        'qty_beli' => toFloat($row['qty_beli'] ?? null),
        'jumlah' => toFloat($row['jumlah'] ?? null),
    ];
}

echo "Excel detail rows grouped by kode: " . count($entriesByKode) . "\n";

$sources = RabBreakdownBudgetSource::query()
    ->whereHas('rabBreakdownItem.rabBreakdown', fn ($q) => $q->where('project_id', $projectId))
    ->with('rabBreakdownItem')
    ->orderBy('id')
    ->get()
    ->groupBy('master_kode');

$updatedSources = 0;
$unmatchedKodes = [];
$leftoverExcel = [];

DB::beginTransaction();
try {
    foreach ($entriesByKode as $kode => $entries) {
        /** @var \Illuminate\Support\Collection<int,RabBreakdownBudgetSource> $sourceRows */
        $sourceRows = $sources->get($kode, collect());
        if ($sourceRows->count() === 0) {
            $unmatchedKodes[] = $kode;
            continue;
        }

        $limit = min(count($entries), $sourceRows->count());
        for ($i = 0; $i < $limit; $i++) {
            $entry = $entries[$i];
            $src = $sourceRows[$i];

            $src->p = $entry['p'];
            $src->l = $entry['l'];
            $src->t = $entry['t'];
            $src->n = $entry['n'];
            $src->n_tul_1 = $entry['n_tul_1'];
            $src->n_tul_2 = $entry['n_tul_2'];
            $src->jarak = $entry['jarak'];
            $src->dia_1 = $entry['dia_1'];
            $src->dia_2 = $entry['dia_2'];
            $src->dia_3 = $entry['dia_3'];
            $src->berat_1 = $entry['berat_1'];
            $src->berat_2 = $entry['berat_2'];
            $src->m2_peng = $entry['m2_peng'];
            $src->qty = $entry['qty'];
            $src->qty_beli = $entry['qty_beli'];
            $src->jumlah = $entry['jumlah'];

            if ($entry['qty_beli'] !== null) {
                $src->allocated_volume = $entry['qty_beli'];
            }
            if ($entry['jumlah'] !== null) {
                $src->allocated_amount = $entry['jumlah'];
            }

            $src->save();
            $updatedSources++;
        }

        if (count($entries) > $sourceRows->count()) {
            $leftoverExcel[$kode] = count($entries) - $sourceRows->count();
        }
    }

    $items = RabBreakdownItem::query()
        ->whereHas('rabBreakdown', fn ($q) => $q->where('project_id', $projectId))
        ->with('budgetSources')
        ->get();

    $updatedItems = 0;
    foreach ($items as $item) {
        if ($item->budgetSources->count() === 0) {
            continue;
        }

        $sumQty = (float) $item->budgetSources->sum(fn ($s) => (float) ($s->qty ?? 0));
        $sumQtyBeli = (float) $item->budgetSources->sum(fn ($s) => (float) ($s->qty_beli ?? $s->allocated_volume ?? 0));
        $sumJumlah = (float) $item->budgetSources->sum(fn ($s) => (float) ($s->jumlah ?? $s->allocated_amount ?? 0));
        $sumM2Peng = (float) $item->budgetSources->sum(fn ($s) => (float) ($s->m2_peng ?? 0));

        $item->qty = $sumQty > 0 ? $sumQty : null;
        $item->qty_beli = $sumQtyBeli > 0 ? $sumQtyBeli : null;
        $item->jumlah = $sumJumlah > 0 ? $sumJumlah : null;
        $item->m2_peng = $sumM2Peng > 0 ? $sumM2Peng : null;
        $item->save();
        $updatedItems++;
    }

    DB::commit();

    echo "DONE\n";
    echo "Updated sources: {$updatedSources}\n";
    echo "Updated items: {$updatedItems}\n";
    echo "Unmatched kode count: " . count($unmatchedKodes) . "\n";
    if (!empty($unmatchedKodes)) {
        echo "Unmatched sample: " . implode(', ', array_slice($unmatchedKodes, 0, 20)) . "\n";
    }
    echo "Leftover excel rows (extra occurrence): " . count($leftoverExcel) . "\n";
    if (!empty($leftoverExcel)) {
        $pairs = [];
        foreach (array_slice($leftoverExcel, 0, 20, true) as $k => $v) {
            $pairs[] = "{$k}:{$v}";
        }
        echo "Leftover sample: " . implode(', ', $pairs) . "\n";
    }
} catch (Throwable $e) {
    DB::rollBack();
    echo 'ERROR: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
