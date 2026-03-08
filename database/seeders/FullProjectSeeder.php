<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Client;
use App\Models\Province;
use App\Models\Vendor;
use App\Models\Project;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Data;
use App\Models\Spp;
use App\Models\SppItem;
use App\Models\Bpg;
use App\Models\BpgItem;
use App\Models\Lpb;
use App\Models\LpbItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\VendorComparison;
use App\Models\VendorComparisonItem;
use App\Models\PurchaseVoucher;
use App\Models\PurchaseVoucherItem;
use App\Models\StockMovement;

class FullProjectSeeder extends Seeder
{
    /**
     * Seed 1 proyek lengkap beserta RAPP dan dokumen turunannya.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // === Province ===
            $province = Province::first();
            if (!$province) {
                $province = Province::firstOrCreate(
                    ['code' => 'JBR'],
                    ['name' => 'Jawa Barat']
                );
            }

            // === Client ===
            $client = Client::firstOrCreate(
                ['name' => 'PT Contoh Konstruksi'],
                [
                    'company' => 'PT Contoh Konstruksi',
                    'email' => 'client@contoh.co.id',
                    'phone' => '021-555000',
                    'address' => 'Jl. Proyek No. 1, Bandung',
                    'category' => 'Korporat',
                    'notes' => 'Client untuk proyek demo lengkap',
                ]
            );

            // === Vendors ===
            $vendorA = Vendor::firstOrCreate([
                'kode_vendor' => 'VEND-001',
            ], [
                'nama' => 'Vendor Material A',
                'perusahaan' => 'CV Material A',
                'pekerjaan' => 'Supplier Material',
                'bank' => 'BCA',
                'no_rekening' => '1234567890',
                'nama_rekening' => 'CV Material A',
                'alamat' => 'Jl. Gudang No. 2',
                'telepon' => '021-555111',
                'email' => 'vendorA@contoh.co.id',
                'status' => 'active',
            ]);

            $vendorB = Vendor::firstOrCreate([
                'kode_vendor' => 'VEND-002',
            ], [
                'nama' => 'Vendor Jasa B',
                'perusahaan' => 'PT Jasa B',
                'pekerjaan' => 'Jasa Pekerjaan',
                'bank' => 'Mandiri',
                'no_rekening' => '9876543210',
                'nama_rekening' => 'PT Jasa B',
                'alamat' => 'Jl. Workshop No. 5',
                'telepon' => '021-555222',
                'email' => 'vendorB@contoh.co.id',
                'status' => 'active',
            ]);

            $vendorC = Vendor::firstOrCreate([
                'kode_vendor' => 'VEND-003',
            ], [
                'nama' => 'Vendor Material C',
                'perusahaan' => 'CV Material C',
                'pekerjaan' => 'Supplier Material',
                'bank' => 'BRI',
                'no_rekening' => '111222333',
                'nama_rekening' => 'CV Material C',
                'alamat' => 'Jl. Logistik No. 8',
                'telepon' => '021-555333',
                'email' => 'vendorC@contoh.co.id',
                'status' => 'active',
            ]);

            // === Master Data Items ===
            $dataSemen = Data::firstOrCreate([
                'kode' => 'MT-001',
            ], [
                'kategori' => 'Material',
                'kode_kategori' => 'MT',
                'uraian' => 'Semen Portland 50kg',
                'spesifikasi' => 'Mutu 42,5',
                'satuan' => 'zak',
                'harga' => 65000,
                'status' => true,
            ]);

            $dataBesi = Data::firstOrCreate([
                'kode' => 'MT-002',
            ], [
                'kategori' => 'Material',
                'kode_kategori' => 'MT',
                'uraian' => 'Besi Beton 10mm',
                'spesifikasi' => 'Panjang 12m',
                'satuan' => 'btg',
                'harga' => 95000,
                'status' => true,
            ]);

            $dataJasa = Data::firstOrCreate([
                'kode' => 'JS-001',
            ], [
                'kategori' => 'Jasa',
                'kode_kategori' => 'JS',
                'uraian' => 'Pekerjaan Pemasangan',
                'spesifikasi' => 'Tenaga ahli + alat bantu',
                'satuan' => 'ls',
                'harga' => 15000000,
                'status' => true,
            ]);

            $dataAlat = Data::firstOrCreate([
                'kode' => 'AT-001',
            ], [
                'kategori' => 'Alat',
                'kode_kategori' => 'AT',
                'uraian' => 'Sewa Alat Berat',
                'spesifikasi' => 'Excavator 20T',
                'satuan' => 'hari',
                'harga' => 2500000,
                'status' => true,
            ]);

            // === Project ===
            $project = Project::firstOrCreate(
                ['code' => 'PROJ-PCM-001'],
                [
                    'name' => 'Proyek Demo PCM',
                    'client_id' => $client->id,
                    'province_id' => $province->id,
                    'location' => 'Bandung, Jawa Barat',
                    'pic' => 'Andi PM',
                    'start_date' => Carbon::now()->subDays(10)->toDateString(),
                    'end_date' => Carbon::now()->addMonths(6)->toDateString(),
                    'budget' => 500000000,
                    'status' => 'Active',
                    'description' => 'Proyek demo lengkap untuk uji alur dokumen.',
                ]
            );

            // === RAPP (RAB) ===
            $rab = Rab::create([
                'project_id' => $project->id,
                'name' => 'RAPP Demo Proyek PCM',
                'status' => 'draft',
                'version' => 1,
                'notes' => 'RAPP demo untuk seluruh dokumen',
            ]);

            $rabItemSemen = RabItem::create([
                'rab_id' => $rab->id,
                'data_id' => $dataSemen->id,
                'item_type' => 'Material',
                'volume' => 200,
                'satuan' => 'zak',
                'harga_satuan' => 65000,
                'keterangan' => 'Kebutuhan struktur awal',
                'urutan' => 1,
            ]);

            $rabItemBesi = RabItem::create([
                'rab_id' => $rab->id,
                'data_id' => $dataBesi->id,
                'item_type' => 'Material',
                'volume' => 120,
                'satuan' => 'btg',
                'harga_satuan' => 95000,
                'keterangan' => 'Penguatan struktur',
                'urutan' => 2,
            ]);

            $rabItemJasa = RabItem::create([
                'rab_id' => $rab->id,
                'data_id' => $dataJasa->id,
                'item_type' => 'Jasa',
                'volume' => 1,
                'satuan' => 'ls',
                'harga_satuan' => 15000000,
                'keterangan' => 'Pekerjaan pemasangan',
                'urutan' => 3,
            ]);

            $rabItemAlat = RabItem::create([
                'rab_id' => $rab->id,
                'data_id' => $dataAlat->id,
                'item_type' => 'Alat',
                'volume' => 10,
                'satuan' => 'hari',
                'harga_satuan' => 2500000,
                'keterangan' => 'Sewa alat berat',
                'urutan' => 4,
            ]);

            $rab->updateTotals();

            // === SPP ===
            $spp = Spp::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'vendor_id' => $vendorA->id,
                'spp_no' => 'SPP-PCM-001',
                'spp_date' => Carbon::now()->subDays(5)->toDateString(),
                'schedule_date' => Carbon::now()->addDays(7)->toDateString(),
                'status' => 'draft',
                'requested_by' => 'Site Engineer',
                'approved_by' => 'PM',
                'notes' => 'Permintaan material awal',
            ]);

            SppItem::create([
                'spp_id' => $spp->id,
                'rab_item_id' => $rabItemSemen->id,
                'vendor_id' => $vendorA->id,
                'item_code_snapshot' => $dataSemen->kode,
                'item_name_snapshot' => $dataSemen->uraian,
                'unit_snapshot' => $dataSemen->satuan,
                'qty' => 100,
                'unit' => 'zak',
                'schedule_date' => Carbon::now()->addDays(7)->toDateString(),
                'work_notes' => 'Tahap 1',
            ]);

            SppItem::create([
                'spp_id' => $spp->id,
                'rab_item_id' => $rabItemBesi->id,
                'vendor_id' => $vendorA->id,
                'item_code_snapshot' => $dataBesi->kode,
                'item_name_snapshot' => $dataBesi->uraian,
                'unit_snapshot' => $dataBesi->satuan,
                'qty' => 60,
                'unit' => 'btg',
                'schedule_date' => Carbon::now()->addDays(7)->toDateString(),
                'work_notes' => 'Tahap 1',
            ]);

            // === Komparasi Vendor ===
            $comparison = VendorComparison::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'comparison_no' => 'KOM-PCM-001',
                'comparison_date' => Carbon::now()->subDays(4)->toDateString(),
                'status' => 'draft',
                'decision_vendor_id' => $vendorA->id,
                'notes' => 'Perbandingan harga material',
            ]);

            VendorComparisonItem::create([
                'vendor_comparison_id' => $comparison->id,
                'rab_item_id' => $rabItemSemen->id,
                'item_code_snapshot' => $dataSemen->kode,
                'item_name_snapshot' => $dataSemen->uraian,
                'unit_snapshot' => $dataSemen->satuan,
                'qty' => 100,
                'unit' => 'zak',
                'rapp_unit_price' => 65000,
                'rapp_total' => 65000 * 100,
                'vendor1_id' => $vendorA->id,
                'vendor1_unit_price' => 64000,
                'vendor1_total' => 64000 * 100,
                'vendor2_id' => $vendorB->id,
                'vendor2_unit_price' => 67000,
                'vendor2_total' => 67000 * 100,
                'vendor3_id' => $vendorC->id,
                'vendor3_unit_price' => 66000,
                'vendor3_total' => 66000 * 100,
            ]);

            VendorComparisonItem::create([
                'vendor_comparison_id' => $comparison->id,
                'rab_item_id' => $rabItemBesi->id,
                'item_code_snapshot' => $dataBesi->kode,
                'item_name_snapshot' => $dataBesi->uraian,
                'unit_snapshot' => $dataBesi->satuan,
                'qty' => 60,
                'unit' => 'btg',
                'rapp_unit_price' => 95000,
                'rapp_total' => 95000 * 60,
                'vendor1_id' => $vendorA->id,
                'vendor1_unit_price' => 94000,
                'vendor1_total' => 94000 * 60,
                'vendor2_id' => $vendorB->id,
                'vendor2_unit_price' => 96000,
                'vendor2_total' => 96000 * 60,
                'vendor3_id' => $vendorC->id,
                'vendor3_unit_price' => 95500,
                'vendor3_total' => 95500 * 60,
            ]);

            $comparison->update([
                'final_amount' => (64000 * 100) + (94000 * 60),
                'difference_amount' => 0,
            ]);

            // === Purchase Order ===
            $poSubtotal = (64000 * 100) + (94000 * 60);
            $poTaxPercent = 11;
            $poTaxAmount = ($poSubtotal * $poTaxPercent) / 100;
            $poShipping = 500000;
            $poTotal = $poSubtotal + $poTaxAmount + $poShipping;

            $po = PurchaseOrder::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'vendor_id' => $vendorA->id,
                'po_no' => 'PO-PCM-001',
                'po_date' => Carbon::now()->subDays(3)->toDateString(),
                'contact_person' => 'Budi',
                'phone' => '081234567890',
                'address' => 'Gudang Proyek Bandung',
                'status' => 'draft',
                'subtotal_amount' => $poSubtotal,
                'tax_percent' => $poTaxPercent,
                'tax_amount' => $poTaxAmount,
                'shipping_cost' => $poShipping,
                'total_amount' => $poTotal,
                'notes' => 'PO material tahap 1',
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'rab_item_id' => $rabItemSemen->id,
                'item_code_snapshot' => $dataSemen->kode,
                'item_name_snapshot' => $dataSemen->uraian,
                'unit_snapshot' => $dataSemen->satuan,
                'specification' => 'Mutu 42,5',
                'qty' => 100,
                'unit' => 'zak',
                'unit_price' => 64000,
                'total_price' => 64000 * 100,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'rab_item_id' => $rabItemBesi->id,
                'item_code_snapshot' => $dataBesi->kode,
                'item_name_snapshot' => $dataBesi->uraian,
                'unit_snapshot' => $dataBesi->satuan,
                'specification' => 'Panjang 12m',
                'qty' => 60,
                'unit' => 'btg',
                'unit_price' => 94000,
                'total_price' => 94000 * 60,
            ]);

            // === LPB (Barang masuk) ===
            $lpb = Lpb::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'vendor_id' => $vendorA->id,
                'purchase_order_id' => $po->id,
                'lpb_no' => 'LPB-PCM-001',
                'lpb_date' => Carbon::now()->subDays(2)->toDateString(),
                'status' => 'draft',
                'delivered_by' => 'Supir Vendor',
                'received_by' => 'Gudang',
                'known_by' => 'Supervisor',
                'notes' => 'Penerimaan material tahap 1',
            ]);

            $lpbItemSemen = LpbItem::create([
                'lpb_id' => $lpb->id,
                'rab_item_id' => $rabItemSemen->id,
                'item_code_snapshot' => $dataSemen->kode,
                'item_name_snapshot' => $dataSemen->uraian,
                'unit_snapshot' => $dataSemen->satuan,
                'qty' => 80,
                'unit' => 'zak',
                'arrival_date' => Carbon::now()->subDays(2)->toDateString(),
                'doc_reference' => 'SJ-001',
                'work_notes' => 'Masuk gudang',
            ]);

            $lpbItemBesi = LpbItem::create([
                'lpb_id' => $lpb->id,
                'rab_item_id' => $rabItemBesi->id,
                'item_code_snapshot' => $dataBesi->kode,
                'item_name_snapshot' => $dataBesi->uraian,
                'unit_snapshot' => $dataBesi->satuan,
                'qty' => 50,
                'unit' => 'btg',
                'arrival_date' => Carbon::now()->subDays(2)->toDateString(),
                'doc_reference' => 'SJ-002',
                'work_notes' => 'Masuk gudang',
            ]);

            StockMovement::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'rab_item_id' => $rabItemSemen->id,
                'movement_type' => 'in',
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'movement_date' => $lpbItemSemen->arrival_date,
                'qty' => $lpbItemSemen->qty,
                'unit' => $lpbItemSemen->unit,
                'notes' => $lpbItemSemen->doc_reference,
            ]);

            StockMovement::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'rab_item_id' => $rabItemBesi->id,
                'movement_type' => 'in',
                'doc_type' => 'lpb',
                'doc_id' => $lpb->id,
                'movement_date' => $lpbItemBesi->arrival_date,
                'qty' => $lpbItemBesi->qty,
                'unit' => $lpbItemBesi->unit,
                'notes' => $lpbItemBesi->doc_reference,
            ]);

            // === BPG (Barang keluar) ===
            $bpg = Bpg::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'bpg_no' => 'BPG-PCM-001',
                'bpg_date' => Carbon::now()->subDay()->toDateString(),
                'status' => 'draft',
                'requested_by' => 'Site Engineer',
                'approved_by' => 'PM',
                'known_by' => 'Gudang',
                'notes' => 'Pengeluaran material ke lapangan',
            ]);

            $bpgItemSemen = BpgItem::create([
                'bpg_id' => $bpg->id,
                'rab_item_id' => $rabItemSemen->id,
                'item_code_snapshot' => $dataSemen->kode,
                'item_name_snapshot' => $dataSemen->uraian,
                'unit_snapshot' => $dataSemen->satuan,
                'qty' => 40,
                'unit' => 'zak',
                'issue_date' => Carbon::now()->subDay()->toDateString(),
                'work_notes' => 'Pemakaian awal',
            ]);

            StockMovement::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'rab_item_id' => $rabItemSemen->id,
                'movement_type' => 'out',
                'doc_type' => 'bpg',
                'doc_id' => $bpg->id,
                'movement_date' => $bpgItemSemen->issue_date,
                'qty' => $bpgItemSemen->qty,
                'unit' => $bpgItemSemen->unit,
                'notes' => $bpgItemSemen->work_notes,
            ]);

            // === SPK (Jasa) ===
            $spk = Spk::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'vendor_id' => $vendorB->id,
                'spk_no' => 'SPK-PCM-001',
                'spk_date' => Carbon::now()->subDays(2)->toDateString(),
                'start_date' => Carbon::now()->subDays(1)->toDateString(),
                'end_date' => Carbon::now()->addDays(14)->toDateString(),
                'status' => 'draft',
                'scope' => 'Pekerjaan pemasangan material',
                'total_amount' => 15000000,
                'notes' => 'SPK jasa pemasangan',
            ]);

            SpkItem::create([
                'spk_id' => $spk->id,
                'rab_item_id' => $rabItemJasa->id,
                'item_code_snapshot' => $dataJasa->kode,
                'item_name_snapshot' => $dataJasa->uraian,
                'unit_snapshot' => $dataJasa->satuan,
                'description' => 'Pemasangan struktur',
                'qty' => 1,
                'unit' => 'ls',
                'unit_price' => 15000000,
                'total_price' => 15000000,
            ]);

            // === Purchase Voucher (Pembayaran) ===
            $voucherSubtotal = (64000 * 80) + (94000 * 50);
            $voucherTaxPercent = 11;
            $voucherTaxAmount = ($voucherSubtotal * $voucherTaxPercent) / 100;
            $voucherShipping = 0;
            $voucherTotal = $voucherSubtotal + $voucherTaxAmount + $voucherShipping;

            $voucher = PurchaseVoucher::create([
                'project_id' => $project->id,
                'rab_id' => $rab->id,
                'vendor_id' => $vendorA->id,
                'purchase_order_id' => $po->id,
                'lpb_id' => $lpb->id,
                'project_code_snapshot' => $project->code,
                'project_name_snapshot' => $project->name,
                'voucher_no' => 'VCH-PCM-001',
                'voucher_date' => Carbon::now()->toDateString(),
                'vendor_name' => $vendorA->nama,
                'bank' => $vendorA->bank,
                'account_no' => $vendorA->no_rekening,
                'account_name' => $vendorA->nama_rekening,
                'transfer_to' => $vendorA->nama,
                'payment_method' => 'transfer',
                'invoice_no' => 'INV-001',
                'payment_purpose' => 'Pembayaran material tahap 1',
                'notes' => 'Voucher pembayaran material sesuai LPB',
                'status' => 'draft',
                'tax_percent' => $voucherTaxPercent,
                'tax_amount' => $voucherTaxAmount,
                'shipping_cost' => $voucherShipping,
                'subtotal_amount' => $voucherSubtotal,
                'total_amount' => $voucherTotal,
            ]);

            PurchaseVoucherItem::create([
                'purchase_voucher_id' => $voucher->id,
                'rab_item_id' => $rabItemSemen->id,
                'qty' => 80,
                'unit' => 'zak',
                'description' => 'Semen Portland 50kg',
                'price' => 64000,
                'amount' => 64000 * 80,
                'rab_harga_satuan_snapshot' => $rabItemSemen->harga_satuan,
                'rab_volume_snapshot' => $rabItemSemen->volume,
            ]);

            PurchaseVoucherItem::create([
                'purchase_voucher_id' => $voucher->id,
                'rab_item_id' => $rabItemBesi->id,
                'qty' => 50,
                'unit' => 'btg',
                'description' => 'Besi Beton 10mm',
                'price' => 94000,
                'amount' => 94000 * 50,
                'rab_harga_satuan_snapshot' => $rabItemBesi->harga_satuan,
                'rab_volume_snapshot' => $rabItemBesi->volume,
            ]);

            // Sync realisasi RAPP dari voucher
            $rabItemSemen->updateRealisasiFromVoucher();
            $rabItemBesi->updateRealisasiFromVoucher();
            $rab->updateTotals();
        });
    }
}
