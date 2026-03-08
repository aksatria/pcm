<?php

namespace App\Services;

use App\Models\MasterData;
use App\Models\MasterCodeCounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExcelImportService
{
    protected $masterDataService;

    public function __construct(MasterDataService $masterDataService)
    {
        $this->masterDataService = $masterDataService;
    }

    public function import($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'imported_data' => []
        ];

        // Skip header row
        array_shift($rows);

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Validate required fields
                    if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                        throw new \Exception("Data tidak lengkap pada baris {$rowNumber}");
                    }

                    $data = [
                        'category' => strtoupper(trim($row[0])),
                        'name' => trim($row[1]),
                        'unit' => trim($row[2]),
                        'price' => $this->parsePrice($row[3]),
                        'description' => $row[4] ?? null,
                        'is_active' => true
                    ];

                    // Validate category
                    if (!in_array($data['category'], ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'])) {
                        throw new \Exception("Kategori '{$data['category']}' tidak valid. Gunakan: MT, JS, AT, HO, SR, SB");
                    }

                    // Validate price
                    if ($data['price'] <= 0) {
                        throw new \Exception("Harga harus lebih dari 0 pada baris {$rowNumber}");
                    }

                    // Check for duplicate (name + category)
                    $existing = MasterData::where('name', $data['name'])
                        ->where('category', $data['category'])
                        ->withTrashed()
                        ->first();

                    if ($existing) {
                        throw new \Exception("Item '{$data['name']}' dengan kategori '{$data['category']}' sudah ada pada baris {$rowNumber}");
                    }

                    // Check for duplicate code if provided
                    if (!empty($row[5])) {
                        $existingCode = MasterData::where('code', trim($row[5]))->withTrashed()->first();
                        if ($existingCode) {
                            throw new \Exception("Kode '{$row[5]}' sudah digunakan pada baris {$rowNumber}");
                        }
                        $data['code'] = trim($row[5]);
                    }

                    $masterData = $this->masterDataService->createItem($data);
                    $results['success']++;
                    $results['imported_data'][] = $masterData;

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Baris {$rowNumber}: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Import gagal: " . $e->getMessage();
        }

        return $results;
    }

    private function parsePrice($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }

        // Handle Excel date format
        if (is_float($value) && $value > 25569) { // Excel date threshold
            try {
                $value = Date::excelToDateTimeObject($value)->format('Y-m-d');
                return 0;
            } catch (\Exception $e) {
                // Continue with normal parsing
            }
        }

        // Handle string price with commas, currency symbols, etc.
        $clean = preg_replace('/[^\d.,]/', '', strval($value));
        
        // Handle European format (1.000,00 -> 1000.00)
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $clean)) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } 
        // Handle US format (1,000.00 -> 1000.00)
        else if (preg_match('/^\d{1,3}(,\d{3})*\.\d+$/', $clean)) {
            $clean = str_replace(',', '', $clean);
        }
        // Handle simple format (1000,00 -> 1000.00)
        else {
            $clean = str_replace(',', '.', $clean);
        }

        return floatval($clean);
    }

    public function generateErrorReport($errors)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Baris');
        $sheet->setCellValue('B1', 'Error Message');

        // Data
        $row = 2;
        foreach ($errors as $error) {
            // Extract row number from error message
            preg_match('/Baris (\d+):/', $error, $matches);
            $rowNumber = $matches[1] ?? 'N/A';
            $message = preg_replace('/Baris \d+:\s*/', '', $error);

            $sheet->setCellValue('A' . $row, $rowNumber);
            $sheet->setCellValue('B' . $row, $message);
            $row++;
        }

        // Style
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFE6E6']
            ]
        ];
        $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        return $spreadsheet;
    }
}