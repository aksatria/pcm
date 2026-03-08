<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RabBreakdownSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting RAB Breakdown Seeder...');
        
        // Project ID yang akan di-seed
        $projectId = 1;
        
        // Pastikan project dengan ID 1 ada
        $this->ensureProjectExists($projectId);
        
        // Data RAB Breakdown standar (11 kategori)
        $wbsList = $this->getWBSData();
        
        // Insert RAB Breakdown utama
        foreach ($wbsList as $wbsData) {
            $wbsId = DB::table('rab_breakdowns')->insertGetId([
                'project_id' => $projectId,
                'rab_breakdown_code' => $wbsData['rab_breakdown_code'],
                'name' => $wbsData['name'],
                'order_number' => $wbsData['order_number'],
                'description' => $wbsData['description'],
                'budget_amount' => $wbsData['budget_amount'],
                'actual_amount' => $wbsData['actual_amount'],
                'progress_percentage' => $wbsData['progress_percentage'],
                'start_date' => $wbsData['start_date'],
                'end_date' => $wbsData['end_date'],
                'status' => $wbsData['status'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            
            $this->command->info("Created RAB Breakdown: {$wbsData['rab_breakdown_code']} - {$wbsData['name']}");
            
            // Tambahkan items untuk setiap RAB Breakdown
            $this->addRabBreakdownItems($wbsId, $wbsData['name'], $wbsData['rab_breakdown_code']);
        }
        
        $totalItems = DB::table('rab_breakdown_items')->count();
        $totalBudgetSources = DB::table('rab_breakdown_budget_sources')->count();
        
        $this->command->info("RAB Breakdown Seeder completed successfully!");
        $this->command->info("Total RAB Breakdown created: " . count($wbsList));
        $this->command->info("Total RAB Breakdown Items created: {$totalItems}");
        $this->command->info("Total Budget Sources created: {$totalBudgetSources}");
        $this->command->info("You can access at: http://localhost:8000/dev/projects/{$projectId}/rab-breakdown");
    }
    
    /**
     * Pastikan project dengan ID tertentu ada
     */
    protected function ensureProjectExists(int $projectId): void
    {
        if (!DB::table('projects')->where('id', $projectId)->exists()) {
            DB::table('projects')->insert([
                'id' => $projectId,
                'name' => 'Project Development RAB Breakdown',
                'code' => 'PROJ-RAB-01',
                'description' => 'Project untuk development dan testing RAB Breakdown',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'budget' => 5000000000,
                'status' => 'active',
                'location' => 'Jakarta',
                'client_id' => null,
                'province_id' => null,
                'created_by' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            
            $this->command->info("Created project with ID: {$projectId}");
        }
    }
    
    /**
     * Data RAB Breakdown
     */
    protected function getWBSData(): array
    {
        return [
            [
                'rab_breakdown_code' => 'RAB-001',
                'name' => 'PEKERJAAN PERSIAPAN',
                'order_number' => 1,
                'description' => 'Pekerjaan persiapan lokasi termasuk mobilisasi peralatan, pemagaran, dan administrasi',
                'budget_amount' => 150000000,
                'actual_amount' => 120000000,
                'progress_percentage' => 80.00,
                'start_date' => '2024-01-15',
                'end_date' => '2024-02-15',
                'status' => 'in_progress'
            ],
            [
                'rab_breakdown_code' => 'RAB-002',
                'name' => 'PEKERJAAN TANAH',
                'order_number' => 2,
                'description' => 'Pekerjaan tanah termasuk galian, urugan, dan pemadatan',
                'budget_amount' => 250000000,
                'actual_amount' => 180000000,
                'progress_percentage' => 72.00,
                'start_date' => '2024-02-01',
                'end_date' => '2024-03-15',
                'status' => 'in_progress'
            ],
            [
                'rab_breakdown_code' => 'RAB-003',
                'name' => 'PEKERJAAN PONDASI',
                'order_number' => 3,
                'description' => 'Pekerjaan pondasi termasuk bore pile, foot plat, dan sloof',
                'budget_amount' => 450000000,
                'actual_amount' => 300000000,
                'progress_percentage' => 66.67,
                'start_date' => '2024-03-01',
                'end_date' => '2024-04-30',
                'status' => 'in_progress'
            ],
            [
                'rab_breakdown_code' => 'RAB-004',
                'name' => 'PEKERJAAN STRUKTUR',
                'order_number' => 4,
                'description' => 'Pekerjaan struktur termasuk kolom, balok, dan plat lantai',
                'budget_amount' => 850000000,
                'actual_amount' => 500000000,
                'progress_percentage' => 58.82,
                'start_date' => '2024-04-15',
                'end_date' => '2024-07-15',
                'status' => 'in_progress'
            ],
            [
                'rab_breakdown_code' => 'RAB-005',
                'name' => 'PEKERJAAN ARSITEKTUR',
                'order_number' => 5,
                'description' => 'Pekerjaan arsitektur termasuk dinding, pintu, dan jendela',
                'budget_amount' => 600000000,
                'actual_amount' => 200000000,
                'progress_percentage' => 33.33,
                'start_date' => '2024-06-01',
                'end_date' => '2024-09-30',
                'status' => 'not_started'
            ],
            [
                'rab_breakdown_code' => 'RAB-006',
                'name' => 'PEKERJAAN MEKANIKAL',
                'order_number' => 6,
                'description' => 'Pekerjaan mekanikal termasuk HVAC dan sistem ventilasi',
                'budget_amount' => 350000000,
                'actual_amount' => 0,
                'progress_percentage' => 0.00,
                'start_date' => '2024-08-01',
                'end_date' => '2024-10-31',
                'status' => 'not_started'
            ],
            [
                'rab_breakdown_code' => 'RAB-007',
                'name' => 'PEKERJAAN ELEKTRIKAL',
                'order_number' => 7,
                'description' => 'Pekerjaan elektrikal termasuk instalasi listrik dan sistem keamanan',
                'budget_amount' => 400000000,
                'actual_amount' => 0,
                'progress_percentage' => 0.00,
                'start_date' => '2024-08-15',
                'end_date' => '2024-11-15',
                'status' => 'not_started'
            ],
            [
                'rab_breakdown_code' => 'RAB-008',
                'name' => 'PEKERJAAN SANITASI & PLAMBING',
                'order_number' => 8,
                'description' => 'Pekerjaan sanitasi dan plambing termasuk instalasi air bersih dan air kotor',
                'budget_amount' => 300000000,
                'actual_amount' => 0,
                'progress_percentage' => 0.00,
                'start_date' => '2024-09-01',
                'end_date' => '2024-11-30',
                'status' => 'not_started'
            ],
            [
                'rab_breakdown_code' => 'RAB-009',
                'name' => 'PEKERJAAN LANSEKAP',
                'order_number' => 9,
                'description' => 'Pekerjaan lansekap termasuk taman dan jalan lingkungan',
                'budget_amount' => 200000000,
                'actual_amount' => 0,
                'progress_percentage' => 0.00,
                'start_date' => '2024-10-01',
                'end_date' => '2024-12-15',
                'status' => 'not_started'
            ],
            [
                'rab_breakdown_code' => 'RAB-010',
                'name' => 'PEKERJAAN FINISHING',
                'order_number' => 10,
                'description' => 'Pekerjaan finishing termasuk cat, keramik, dan plafon',
                'budget_amount' => 550000000,
                'actual_amount' => 0,
                'progress_percentage' => 0.00,
                'start_date' => '2024-10-15',
                'end_date' => '2025-01-15',
                'status' => 'not_started'
            ],
            [
                'rab_breakdown_code' => 'RAB-011',
                'name' => 'PENGADAAN PERABOT & PERALATAN',
                'order_number' => 11,
                'description' => 'Pengadaan perabot dan peralatan kantor',
                'budget_amount' => 250000000,
                'actual_amount' => 0,
                'progress_percentage' => 0.00,
                'start_date' => '2025-01-01',
                'end_date' => '2025-02-28',
                'status' => 'not_started'
            ]
        ];
    }
    
    /**
     * Add RAB Breakdown items for each category
     */
    protected function addRabBreakdownItems($wbsId, $wbsName, $wbsCode): void
    {
        $items = $this->getRabBreakdownItems($wbsCode);
        
        foreach ($items as $item) {
            list($itemCode, $uraian, $volume, $satuan, $unitPrice) = $item;
            $totalPrice = $volume * $unitPrice;
            
            $itemId = DB::table('rab_breakdown_items')->insertGetId([
                'rab_breakdown_id' => $wbsId,
                'item_code' => $itemCode,
                'uraian' => $uraian,
                'volume_rab' => $volume,
                'satuan' => $satuan,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'rab_item_id' => null,
                'notes' => 'Item untuk ' . $wbsName,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            
            // Tambahkan budget source untuk item pertama
            if ($itemCode === $items[0][0]) {
                $this->addBudgetSource($itemId, $itemCode, $uraian, $unitPrice, $volume);
            }
        }
    }
    
    /**
     * Get items for each RAB Breakdown category
     */
    protected function getRabBreakdownItems(string $wbsCode): array
    {
        return match($wbsCode) {
            'RAB-001' => [
                ['A.1', 'Mobilisasi peralatan dan tenaga kerja', 1, 'LS', 25000000],
                ['A.2', 'Pembuatan rumah kantor proyek', 1, 'unit', 35000000],
                ['A.3', 'Pemagaran sementara area proyek', 200, 'm', 75000],
            ],
            'RAB-002' => [
                ['B.1', 'Galian tanah biasa', 850, 'm³', 85000],
                ['B.2', 'Urugan tanah kembali', 520, 'm³', 65000],
                ['B.3', 'Pemadatan tanah', 850, 'm²', 35000],
            ],
            'RAB-003' => [
                ['C.1', 'Pondasi bore pile diameter 60cm', 42, 'm³', 2850000],
                ['C.2', 'Pile cap beton K-300', 65, 'm³', 2450000],
                ['C.3', 'Sloof beton bertulang', 38, 'm³', 1950000],
            ],
            'RAB-004' => [
                ['D.1', 'Kolom struktur beton K-300', 185, 'm³', 2850000],
                ['D.2', 'Balok induk beton bertulang', 92, 'm³', 2650000],
                ['D.3', 'Plat lantai beton', 480, 'm²', 1850000],
            ],
            'RAB-005' => [
                ['E.1', 'Dinding bata merah 1:4', 2850, 'm²', 285000],
                ['E.2', 'Plesteran dinding 1:4', 5700, 'm²', 125000],
                ['E.3', 'Pintu kayu kamper', 28, 'unit', 2850000],
            ],
            'RAB-006' => [
                ['F.1', 'Instalasi AC split unit', 28, 'unit', 8500000],
                ['F.2', 'Ducting HVAC', 850, 'm', 285000],
                ['F.3', 'Exhaust fan', 18, 'unit', 1850000],
            ],
            'RAB-007' => [
                ['G.1', 'Instalasi listrik tenaga', 1, 'LS', 85000000],
                ['G.2', 'Instalasi listrik penerangan', 1, 'LS', 65000000],
                ['G.3', 'Panel listrik utama', 4, 'unit', 28500000],
            ],
            'RAB-008' => [
                ['H.1', 'Instalasi air bersih', 1, 'LS', 65000000],
                ['H.2', 'Instalasi air kotor', 1, 'LS', 55000000],
                ['H.3', 'Septictank beton', 4, 'unit', 12500000],
            ],
            'RAB-009' => [
                ['I.1', 'Paving block jalan', 1850, 'm²', 285000],
                ['I.2', 'Taman dan tanaman hias', 850, 'm²', 450000],
                ['I.3', 'Lampu taman LED', 42, 'unit', 1850000],
            ],
            'RAB-010' => [
                ['J.1', 'Cat dinding interior/exterior', 5700, 'm²', 125000],
                ['J.2', 'Keramik lantai 60x60', 1850, 'm²', 285000],
                ['J.3', 'Plafon gypsum', 1850, 'm²', 385000],
            ],
            'RAB-011' => [
                ['K.1', 'Meja kerja kantor', 42, 'unit', 2850000],
                ['K.2', 'Kursi kantor ergonomis', 85, 'unit', 1850000],
                ['K.3', 'Lemari arsip', 28, 'unit', 3850000],
            ],
            default => [],
        };
    }
    
    /**
     * Add budget source for item
     */
    protected function addBudgetSource($itemId, $itemCode, $uraian, $unitPrice, $volume): void
    {
        $categoryMap = [
            'A' => 'JS', 'B' => 'JS', 'C' => 'MT', 'D' => 'MT',
            'E' => 'MT', 'F' => 'SR', 'G' => 'SR', 'H' => 'MT',
            'I' => 'JS', 'J' => 'MT', 'K' => 'MT'
        ];
        
        $prefix = substr($itemCode, 0, 1);
        $kategoriCode = $categoryMap[$prefix] ?? 'MT';
        
        $kategoriNama = match($kategoriCode) {
            'JS' => 'JASA',
            'MT' => 'MATERIAL',
            'SR' => 'SUBKON',
            default => 'MATERIAL'
        };
        
        $counter = rand(1, 100);
        $masterKode = $kategoriCode . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
        
        DB::table('rab_breakdown_budget_sources')->insert([
            'rab_breakdown_item_id' => $itemId,
            'master_kode' => $masterKode,
            'master_kategori' => $kategoriNama,
            'master_uraian' => substr($uraian, 0, 200),
            'master_satuan' => 'LS',
            'master_harga' => $unitPrice,
            'allocated_volume' => $volume,
            'allocated_amount' => $volume * $unitPrice,
            'notes' => 'Auto-generated from seeder',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}


