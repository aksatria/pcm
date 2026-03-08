<?php

namespace App\Imports;

use App\Models\RabItem;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RabItemsImport
{
    protected $project;
    protected $importedCount = 0;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function import($file)
    {
        try {
            // Load Excel file menggunakan PhpSpreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row
            array_shift($rows);
            
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    // Skip empty rows
                    if (empty($row[0]) || empty($row[1])) {
                        continue;
                    }

                    try {
                        // Map columns based on template structure
                        $code = $row[0] ?? '';
                        $description = $row[1] ?? '';
                        $unit = $row[2] ?? 'ls';
                        $volume = $this->parseVolume($row[3] ?? '0');
                        $unitPrice = $this->parseUnitPrice($row[4] ?? '0');
                        
                        $totalPrice = $volume * $unitPrice;
                        $category = $this->determineCategory($code);

                        RabItem::create([
                            'project_id' => $this->project->id,
                            'code' => $code,
                            'category' => $category,
                            'description' => $description,
                            'unit' => $unit,
                            'volume' => $volume,
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                            'notes' => null,
                            'order' => $this->getNextOrder($category),
                        ]);

                        $this->importedCount++;

                    } catch (\Exception $e) {
                        Log::error('RAB Item import failed for row', [
                            'project_id' => $this->project->id,
                            'row_data' => $row,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with next row
                    }
                }
            });

            return $this->importedCount;

        } catch (\Exception $e) {
            Log::error('Excel import failed', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    /**
     * Parse volume (format: 1,00 -> 1.00)
     */
    private function parseVolume($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        if (is_string($value)) {
            // Convert comma to dot for decimal
            $value = str_replace(',', '.', $value);
            // Remove any non-numeric characters except dot
            $value = preg_replace('/[^0-9.]/', '', $value);
        }
        
        return floatval($value);
    }

    /**
     * Parse unit price (format: 2.500.000 -> 2500000, 17,50 -> 17.50)
     */
    private function parseUnitPrice($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }

        if (is_string($value)) {
            // Remove thousand separators (dots and spaces)
            $value = str_replace(['.', ' ', 'Rp', 'IDR'], '', $value);
            
            // Convert decimal comma to dot
            $value = str_replace(',', '.', $value);
            
            // Remove any non-numeric characters except dot
            $value = preg_replace('/[^0-9.]/', '', $value);
        }
        
        return floatval($value);
    }

    /**
     * Determine category from code prefix
     */
    private function determineCategory($code)
    {
        $prefix = substr($code, 0, 2);
        
        $categoryMap = [
            'MT' => 'MATERIAL',
            'JS' => 'JASA',
            'AT' => 'ALAT',
            'HO' => 'OVERHEAD',
            'SR' => 'SIRKULASI',
            'SB' => 'SUBKON',
        ];

        return $categoryMap[$prefix] ?? 'MATERIAL';
    }

    private function getNextOrder($category)
    {
        $lastOrder = $this->project->rabItems()
            ->where('category', $category)
            ->max('order');

        return ($lastOrder ?? 0) + 1;
    }
}