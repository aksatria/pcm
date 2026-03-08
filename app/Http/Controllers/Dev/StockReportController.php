<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Rab;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class StockReportController extends Controller
{
    public function rekap()
    {
        $projects = Project::orderBy('name')->get();

        return view('dev.stock.rekap', compact('projects'));
    }

    public function kartu()
    {
        $projects = Project::orderBy('name')->get();

        return view('dev.stock.kartu', compact('projects'));
    }

    public function rekapData(Request $request)
    {
        $projectId = $request->get('project_id');
        if (!$projectId) {
            return response()->json([]);
        }
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->selectRaw("rab_item_id,
                        SUM(CASE WHEN movement_type = 'in' THEN qty ELSE 0 END) AS qty_in,
                        SUM(CASE WHEN movement_type = 'out' THEN qty ELSE 0 END) AS qty_out")
            ->groupBy('rab_item_id')
            ->with('rabItem.data')
            ->get();

        return response()->json($rows);
    }

    public function kartuData(Request $request)
    {
        $projectId = $request->get('project_id');
        if (!$projectId) {
            return response()->json([]);
        }
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->with('rabItem.data')
            ->orderBy('movement_date')
            ->get();

        return response()->json($rows);
    }

    public function rekapPrint(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->selectRaw("rab_item_id,
                        SUM(CASE WHEN movement_type = 'in' THEN qty ELSE 0 END) AS qty_in,
                        SUM(CASE WHEN movement_type = 'out' THEN qty ELSE 0 END) AS qty_out")
            ->groupBy('rab_item_id')
            ->with('rabItem.data')
            ->get();

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.stock_rekap', compact('project', 'rab', 'rows', 'dateFrom', 'dateTo', 'logoSrc'));
    }

    public function rekapPdf(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->selectRaw("rab_item_id,
                        SUM(CASE WHEN movement_type = 'in' THEN qty ELSE 0 END) AS qty_in,
                        SUM(CASE WHEN movement_type = 'out' THEN qty ELSE 0 END) AS qty_out")
            ->groupBy('rab_item_id')
            ->with('rabItem.data')
            ->get();

        $data = compact('project', 'rab', 'rows', 'dateFrom', 'dateTo');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.stock_rekap', $data)
                ->setPaper('a4', 'portrait')
                ->stream('Rekap-Stock.pdf');
        }

        return view('pdf.stock_rekap', $data);
    }

    public function kartuPrint(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->with('rabItem.data')
            ->orderBy('movement_date')
            ->get();

        $logoSrc = asset('images/logo-dipo.png');
        return view('pdf.stock_kartu', compact('project', 'rab', 'rows', 'dateFrom', 'dateTo', 'logoSrc'));
    }

    public function kartuPdf(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->with('rabItem.data')
            ->orderBy('movement_date')
            ->get();

        $data = compact('project', 'rab', 'rows', 'dateFrom', 'dateTo');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.stock_kartu', $data)
                ->setPaper('a4', 'landscape')
                ->stream('Kartu-Stock.pdf');
        }

        return view('pdf.stock_kartu', $data);
    }

    public function rekapExcel(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->selectRaw("rab_item_id,
                        SUM(CASE WHEN movement_type = 'in' THEN qty ELSE 0 END) AS qty_in,
                        SUM(CASE WHEN movement_type = 'out' THEN qty ELSE 0 END) AS qty_out")
            ->groupBy('rab_item_id')
            ->with('rabItem.data')
            ->get();

        $filename = 'Rekap-Stock-' . ($project?->code ?? 'Project') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Stock');

        $sheet->setCellValue('C1', 'REKAP STOCK GUDANG');
        $sheet->mergeCells('C1:F1');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $logoPath = public_path('images/logo-dipo.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setPath($logoPath);
            $drawing->setHeight(32);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        }

        $sheet->setCellValue('A3', 'Proyek');
        $sheet->setCellValue('B3', $project?->name ?? '-');
        $sheet->setCellValue('A4', 'RAPP');
        $sheet->setCellValue('B4', $rab?->name ?? ($rab?->id ? 'RAPP #' . $rab->id : '-'));
        $sheet->setCellValue('A5', 'Periode');
        $sheet->setCellValue('B5', trim(($dateFrom ?? '-') . ' s/d ' . ($dateTo ?? '-')));

        $headerRow = 7;
        $headers = ['Kode Item', 'Item', 'Satuan', 'Penerimaan', 'Pengeluaran', 'Sisa'];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9EFF7');
        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowIndex = $headerRow + 1;
        foreach ($rows as $row) {
            $unit = $row->rabItem?->unit ?? $row->rabItem?->data?->satuan ?? '';
            $qtyIn = (float) ($row->qty_in ?? 0);
            $qtyOut = (float) ($row->qty_out ?? 0);
            $sisa = $qtyIn - $qtyOut;
            $sheet->setCellValue("A{$rowIndex}", $row->rabItem?->data?->kode ?? '');
            $sheet->setCellValue("B{$rowIndex}", $row->rabItem?->data?->uraian ?? '');
            $sheet->setCellValue("C{$rowIndex}", $unit);
            $sheet->setCellValue("D{$rowIndex}", $qtyIn);
            $sheet->setCellValue("E{$rowIndex}", $qtyOut);
            $sheet->setCellValue("F{$rowIndex}", $sisa);
            $rowIndex++;
        }

        if ($rowIndex === $headerRow + 1) {
            $sheet->setCellValue("A{$rowIndex}", 'Tidak ada data.');
            $sheet->mergeCells("A{$rowIndex}:F{$rowIndex}");
            $rowIndex++;
        }

        $sheet->getStyle("A{$headerRow}:F" . ($rowIndex - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("D" . ($headerRow + 1) . ":F" . ($rowIndex - 1))
            ->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'rekap_stock_');
        $writer->save($tmpFile);

        return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
    }

    public function kartuExcel(Request $request)
    {
        $projectId = $request->get('project_id');
        $project = $projectId ? Project::findOrFail($projectId) : null;
        $rab = $projectId ? Rab::where('project_id', $projectId)->orderByDesc('created_at')->first() : null;
        $rabId = $rab?->id;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $rows = StockMovement::query()
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
            ->when($dateFrom, fn($q) => $q->whereRaw('date(movement_date) >= ?', [$dateFrom]))
            ->when($dateTo, fn($q) => $q->whereRaw('date(movement_date) <= ?', [$dateTo]))
            ->with('rabItem.data')
            ->orderBy('movement_date')
            ->get();

        $filename = 'Kartu-Stock-' . ($project?->code ?? 'Project') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kartu Stock');

        $sheet->setCellValue('C1', 'KARTU STOCK GUDANG');
        $sheet->mergeCells('C1:G1');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $logoPath = public_path('images/logo-dipo.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setPath($logoPath);
            $drawing->setHeight(32);
            $drawing->setCoordinates('A1');
            $drawing->setWorksheet($sheet);
        }

        $sheet->setCellValue('A3', 'Proyek');
        $sheet->setCellValue('B3', $project?->name ?? '-');
        $sheet->setCellValue('A4', 'RAPP');
        $sheet->setCellValue('B4', $rab?->name ?? ($rab?->id ? 'RAPP #' . $rab->id : '-'));
        $sheet->setCellValue('A5', 'Periode');
        $sheet->setCellValue('B5', trim(($dateFrom ?? '-') . ' s/d ' . ($dateTo ?? '-')));

        $headerRow = 7;
        $headers = ['Tanggal', 'Kode Item', 'Item', 'Satuan', 'Tipe', 'Qty', 'Saldo'];
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9EFF7');
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowIndex = $headerRow + 1;
        $running = [];
        foreach ($rows as $row) {
            $rabItemId = $row->rab_item_id;
            $unit = $row->rabItem?->unit ?? $row->rabItem?->data?->satuan ?? '';
            $qty = (float) ($row->qty ?? 0);
            $prev = $running[$rabItemId] ?? 0;
            $next = $prev + ((($row->movement_type ?? '') === 'in') ? $qty : -$qty);
            $running[$rabItemId] = $next;

            $sheet->setCellValue("A{$rowIndex}", optional($row->movement_date)->format('Y-m-d'));
            $sheet->setCellValue("B{$rowIndex}", $row->rabItem?->data?->kode ?? '');
            $sheet->setCellValue("C{$rowIndex}", $row->rabItem?->data?->uraian ?? '');
            $sheet->setCellValue("D{$rowIndex}", $unit);
            $sheet->setCellValue("E{$rowIndex}", ($row->movement_type ?? ''));
            $sheet->setCellValue("F{$rowIndex}", $qty);
            $sheet->setCellValue("G{$rowIndex}", $next);
            $rowIndex++;
        }

        if ($rowIndex === $headerRow + 1) {
            $sheet->setCellValue("A{$rowIndex}", 'Tidak ada data.');
            $sheet->mergeCells("A{$rowIndex}:G{$rowIndex}");
            $rowIndex++;
        }

        $sheet->getStyle("A{$headerRow}:G" . ($rowIndex - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("F" . ($headerRow + 1) . ":G" . ($rowIndex - 1))
            ->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'kartu_stock_');
        $writer->save($tmpFile);

        return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
    }
}


