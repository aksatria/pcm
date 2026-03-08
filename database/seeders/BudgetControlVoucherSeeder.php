<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\BudgetControl;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Models\VoucherItem;

class BudgetControlVoucherSeeder extends Seeder
{
    public function run()
    {
        try {
            // Cek atau buat Project
            $project = Project::first();
            
            if (!$project) {
                $project = Project::create([
                    'code' => 'PROJ-BCV-001',
                    'name' => 'Project Budget Control & Voucher Test',
                    'description' => 'Project untuk testing Budget Control dan Voucher System',
                    'start_date' => now(),
                    'end_date' => now()->addMonths(6),
                    'budget' => 500000000,
                    'status' => 'active',
                    'project_manager' => 'Manager Test',
                ]);
                
                echo "Project created: {$project->name} (ID: {$project->id})\n";
            } else {
                echo "Using existing project: {$project->name} (ID: {$project->id})\n";
            }

            // 1. Cek atau buat Vendor
            $vendor = Vendor::where('kode_vendor', 'VEND-001')->first();
            
            if (!$vendor) {
                $vendor = Vendor::create([
                    'kode_vendor' => 'VEND-001',
                    'nama' => 'Supplier Test',
                    'perusahaan' => 'PT. Supplier Jaya Abadi',
                    'pekerjaan' => 'Supplier Material Konstruksi',
                    'bank' => 'BCA',
                    'no_rekening' => '1234567890',
                    'nama_rekening' => 'Supplier Test',
                    'alamat' => 'Jl. Raya Industri No. 123',
                    'telepon' => '081234567890',
                    'email' => 'supplier@test.com',
                    'status' => 'active'
                ]);
                
                echo "Vendor created: {$vendor->nama}\n";
            } else {
                echo "Using existing vendor: {$vendor->nama}\n";
            }

            // 2. Cek apakah Budget Controls sudah ada untuk project ini
            $existingBudgetControls = BudgetControl::where('project_id', $project->id)->count();
            
            if ($existingBudgetControls > 0) {
                echo "Budget Controls already exist for this project. Skipping creation.\n";
                $budgetControls = BudgetControl::where('project_id', $project->id)->get();
            } else {
                // Buat Budget Controls
                $budgetControlsData = [
                    [
                        'project_id' => $project->id,
                        'kode' => 'MAT-BC-001',
                        'uraian' => 'Material Besi Beton 10mm',
                        'satuan' => 'kg',
                        'volume_plan' => 5000,
                        'harga_satuan_plan' => 18000,
                        'jumlah_plan' => 90000000,
                        'volume_real' => 0,
                        'jumlah_real' => 0,
                        'kategori' => 'MT',
                        'status' => 'active',
                        'is_selected' => true,
                        'keterangan' => 'Material utama proyek'
                    ],
                    [
                        'project_id' => $project->id,
                        'kode' => 'MAT-BC-002',
                        'uraian' => 'Material Semen',
                        'satuan' => 'zak',
                        'volume_plan' => 1000,
                        'harga_satuan_plan' => 70000,
                        'jumlah_plan' => 70000000,
                        'volume_real' => 0,
                        'jumlah_real' => 0,
                        'kategori' => 'MT',
                        'status' => 'active',
                        'is_selected' => true,
                        'keterangan' => 'Semen proyek'
                    ],
                    [
                        'project_id' => $project->id,
                        'kode' => 'JASA-BC-001',
                        'uraian' => 'Jasa Pemasangan Struktur',
                        'satuan' => 'hari',
                        'volume_plan' => 60,
                        'harga_satuan_plan' => 1500000,
                        'jumlah_plan' => 90000000,
                        'volume_real' => 0,
                        'jumlah_real' => 0,
                        'kategori' => 'JS',
                        'status' => 'active',
                        'is_selected' => false,
                        'keterangan' => 'Jasa kontraktor'
                    ],
                    [
                        'project_id' => $project->id,
                        'kode' => 'ALAT-BC-001',
                        'uraian' => 'Sewa Excavator',
                        'satuan' => 'jam',
                        'volume_plan' => 200,
                        'harga_satuan_plan' => 250000,
                        'jumlah_plan' => 50000000,
                        'volume_real' => 0,
                        'jumlah_real' => 0,
                        'kategori' => 'AT',
                        'status' => 'active',
                        'is_selected' => true,
                        'keterangan' => 'Sewa alat berat'
                    ]
                ];

                $budgetControls = [];
                foreach ($budgetControlsData as $data) {
                    $bc = BudgetControl::create($data);
                    $budgetControls[] = $bc;
                    echo "Budget Control created: {$bc->kode} - {$bc->uraian} (Kategori: {$bc->kategori})\n";
                }
            }

            // 3. Cek apakah Voucher sudah ada
            // Gunakan try-catch untuk handle jika model Voucher tidak ditemukan
            try {
                $voucher = Voucher::where('project_id', $project->id)
                    ->where('vendor_id', $vendor->id)
                    ->first();
            } catch (\Exception $e) {
                echo "ERROR: Voucher model not found. Creating new one...\n";
                $voucher = null;
            }
            
            if (!$voucher) {
                // Buat Voucher
                $voucher = Voucher::create([
                    'project_id' => $project->id,
                    'vendor_id' => $vendor->id,
                    'voucher_number' => null,
                    'tanggal' => now(),
                    'tujuan_transfer' => 'Pembayaran material besi beton dan semen',
                    'bank' => 'BCA',
                    'nama_rekening' => $vendor->nama_rekening,
                    'no_rekening' => $vendor->no_rekening,
                    'pembayaran' => 'transfer',
                    'jatuh_tempo' => now()->addDays(30),
                    'diajukan_oleh' => 'Admin Project',
                    'status' => 'draft',
                    'ppn' => 900000,
                    'ongkir' => 500000,
                    'keterangan' => 'Pembayaran tahap 1 untuk material'
                ]);
                
                echo "Voucher created: {$voucher->voucher_number}\n";

                // 4. Buat Voucher Items
                $voucherItemsData = [
                    [
                        'voucher_id' => $voucher->id,
                        'budget_control_id' => $budgetControls[0]->id,
                        'kode' => 'MAT-BC-001',
                        'uraian' => 'Besi Beton 10mm',
                        'qty' => 1000,
                        'satuan' => 'kg',
                        'harga_satuan' => 18000,
                        'total_harga' => 18000000,
                        'status' => 'pending',
                        'keterangan' => 'Pengiriman pertama'
                    ],
                    [
                        'voucher_id' => $voucher->id,
                        'budget_control_id' => $budgetControls[1]->id,
                        'kode' => 'MAT-BC-002',
                        'uraian' => 'Semen Portland',
                        'qty' => 200,
                        'satuan' => 'zak',
                        'harga_satuan' => 70000,
                        'total_harga' => 14000000,
                        'status' => 'pending',
                        'keterangan' => 'Semen untuk tahap awal'
                    ]
                ];

                foreach ($voucherItemsData as $itemData) {
                    $item = VoucherItem::create($itemData);
                    echo "Voucher Item created: {$item->kode} - Rp " . number_format($item->total_harga, 0, ',', '.') . "\n";
                }

                // 5. Hitung total voucher
                $voucher->calculateTotals();
                $voucher->refresh();

                echo "Voucher totals calculated:\n";
                echo "  Total Tagihan: Rp " . number_format($voucher->total_tagihan, 0, ',', '.') . "\n";
                echo "  PPN: Rp " . number_format($voucher->ppn, 0, ',', '.') . "\n";
                echo "  Ongkir: Rp " . number_format($voucher->ongkir, 0, ',', '.') . "\n";
                echo "  Total Bayar: Rp " . number_format($voucher->total_bayar, 0, ',', '.') . "\n";

                // 6. Update status voucher menjadi submitted
                $voucher->status = 'submitted';
                $voucher->tanggal_pengajuan = now();
                $voucher->save();
                
                echo "Voucher status updated to: {$voucher->status}\n";
            } else {
                echo "Voucher already exists: {$voucher->voucher_number} (Status: {$voucher->status})\n";
            }

            echo "\n=== SEEDER COMPLETED SUCCESSFULLY ===\n";
            echo "Access Budget Controls: /dev/projects/{$project->id}/budget-controls\n";
            echo "Access Vouchers: /dev/projects/{$project->id}/vouchers\n";
            echo "Access Vendor: /dev/vendors\n";
            
        } catch (\Exception $e) {
            echo "\n=== ERROR OCCURRED ===\n";
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "\nTroubleshooting steps:\n";
            echo "1. Check if Voucher model exists: app/Models/Voucher.php\n";
            echo "2. Run: php artisan optimize:clear\n";
            echo "3. Run: composer dump-autoload\n";
            echo "4. Check database migrations\n";
        }
    }
}