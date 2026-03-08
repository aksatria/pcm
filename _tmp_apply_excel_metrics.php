<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

use App\Models\RabBreakdownBudgetSource;
use App\Models\RabBreakdownItem;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$excelPath = 'C:\\Users\\meetm\\Downloads\\Pengajuan RAPP cafe gresik rev.1.xlsm';
$projectId = 1;
$sheetName = 'RAPP MASTER';

if (!file_exists($excelPath)) {
    echo "ERROR: Excel file not found: {$excelPath}\n";
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

echo "Loading workbook...\n";
$spreadsheet = IOFactory::load($excelPath);
$sheet = $spreadsheet->getSheetByName($sheetName);
if (!$sheet) {
    echo "ERROR: Sheet '{$sheetName}' not found.\n";
    exit(1);
}

$highestRow = $sheet->getHighestDataRow();
echo "Sheet rows: {$highestRow}\n";

$entriesByKode = [];
for ($row = 2; $row <= $highestRow; $row++) {
    $kode = trim((string) $sheet->getCell("E{$row}")->getCalculatedValue());
    if ($kode === '') {
        continue;
    }

    $entriesByKode[$kode][] = [
        'row' => $row,
        'p' => toFloat($sheet->getCell("J{$row}")->getCalculatedValue()),
        'l' => toFloat($sheet->getCell("K{$row}")->getCalculatedValue()),
        't' => toFloat($sheet->getCell("L{$row}")->getCalculatedValue()),
        'n' => toFloat($sheet->getCell("M{$row}")->getCalculatedValue()),
        'n_tul_1' => toFloat($sheet->getCell("N{$row}")->getCalculatedValue()),
        'n_tul_2' => toFloat($sheet->getCell("O{$row}")->getCalculatedValue()),
        'jarak' => toFloat($sheet->getCell("P{$row}")->getCalculatedValue()),
        'dia_1' => toFloat($sheet->getCell("Q{$row}")->getCalculatedValue()),
        'dia_2' => toFloat($sheet->getCell("R{$row}")->getCalculatedValue()),
        'dia_3' => toFloat($sheet->getCell("S{$row}")->getCalculatedValue()),
        'berat_1' => toFloat($sheet->getCell("T{$row}")->getCalculatedValue()),
        'berat_2' => toFloat($sheet->getCell("U{$row}")->getCalculatedValue()),
        'm2_peng' => toFloat($sheet->getCell("V{$row}")->getCalculatedValue()),
        'qty' => toFloat($sheet->getCell("W{$row}")->getCalculatedValue()),
        'qty_beli' => toFloat($sheet->getCell("X{$row}")->getCalculatedValue()),
        'jumlah' => toFloat($sheet->getCell("Y{$row}")->getCalculatedValue()),
    ];
}

echo "Unique kode in Excel: " . count($entriesByKode) . "\n";

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

    // Rollup ke level item supaya index menggunakan nilai explicit.
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
    echo "Leftover excel rows (kode with extra rows): " . count($leftoverExcel) . "\n";
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

