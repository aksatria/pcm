<?php

namespace Database\Seeders;

use App\Models\Bpg;
use App\Models\BpgItem;
use App\Models\Data;
use App\Models\Lpb;
use App\Models\LpbItem;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseVoucher;
use App\Models\PurchaseVoucherItem;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\Spp;
use App\Models\SppItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorComparison;
use App\Models\VendorComparisonItem;
use App\Models\RabBreakdownBudgetSource;
use App\Models\RabBreakdownItem;
use App\Models\RabBreakdown;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Project1Rapp2FullFlowSeeder extends Seeder
{
    protected function targetProjectId(): int
    {
        return 1;
    }

    protected function targetRabId(): int
    {
        return 2;
    }

    protected function flowCode(): string
    {
        return 'P1R2';
    }

    public function run(): void
    {
        $projectId = $this->targetProjectId();
        $rabId = $this->targetRabId();
        $flowCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $this->flowCode() ?: "P{$projectId}R{$rabId}"));

        $project = Project::find($projectId);
        $rab = Rab::where('project_id', $projectId)->find($rabId);

        if (!$project || !$rab) {
            $this->command->error("Seeder dibatalkan: project #{$projectId} atau RAPP #{$rabId} tidak ditemukan.");
            return;
        }

        DB::transaction(function () use ($project, $rab, $flowCode): void {
            $today = Carbon::today();
            $seedTag = '[SEED ' . $flowCode . ']';
            $firstUser = User::query()->orderBy('id')->first();
            $firstUserId = $firstUser?->id;
            $firstUserName = $firstUser?->name ?? 'Seeder';

            $vendorA = Vendor::updateOrCreate(
                ['kode_vendor' => 'SEED-' . $flowCode . '-V001'],
                [
                    'project_id' => null,
                    'nama' => 'Vendor Seed Material A',
                    'perusahaan' => 'CV Vendor Seed A',
                    'pekerjaan' => 'Supplier Material',
                    'bank' => 'BCA',
                    'no_rekening' => '1002003001',
                    'nama_rekening' => 'CV Vendor Seed A',
                    'alamat' => 'Gudang Seed A',
                    'telepon' => '081111111111',
                    'email' => 'seed-vendor-a@example.com',
                    'status' => 'active',
                ]
            );

            $vendorB = Vendor::updateOrCreate(
                ['kode_vendor' => 'SEED-' . $flowCode . '-V002'],
                [
                    'project_id' => null,
                    'nama' => 'Vendor Seed Material B',
                    'perusahaan' => 'CV Vendor Seed B',
                    'pekerjaan' => 'Supplier Material',
                    'bank' => 'Mandiri',
                    'no_rekening' => '1002003002',
                    'nama_rekening' => 'CV Vendor Seed B',
                    'alamat' => 'Gudang Seed B',
                    'telepon' => '082222222222',
                    'email' => 'seed-vendor-b@example.com',
                    'status' => 'active',
                ]
            );

            $vendorC = Vendor::updateOrCreate(
                ['kode_vendor' => 'SEED-' . $flowCode . '-V003'],
                [
                    'project_id' => null,
                    'nama' => 'Vendor Seed Material C',
                    'perusahaan' => 'CV Vendor Seed C',
                    'pekerjaan' => 'Supplier Material',
                    'bank' => 'BRI',
                    'no_rekening' => '1002003003',
                    'nama_rekening' => 'CV Vendor Seed C',
                    'alamat' => 'Gudang Seed C',
                    'telepon' => '083333333333',
                    'email' => 'seed-vendor-c@example.com',
                    'status' => 'active',
                ]
            );

            $rabItems = RabItem::where('rab_id', $rab->id)->orderBy('id')->get();
            if ($rabItems->count() < 3) {
                $needed = 3 - $rabItems->count();
                $dataItems = Data::orderBy('id')->take($needed)->get();
                $lastOrder = (int) (RabItem::where('rab_id', $rab->id)->max('urutan') ?? 0);
                foreach ($dataItems as $idx => $data) {
                    RabItem::create([
                        'rab_id' => $rab->id,
                        'data_id' => $data->id,
                        'item_type' => $data->kategori ?? 'Material',
                        'volume' => 30,
                        'satuan' => $data->satuan ?? 'unit',
                        'harga_satuan' => max(1, (float) ($data->harga ?? 10000)),
                        'keterangan' => $data->uraian ?? $data->nama ?? 'Item Seed',
                        'urutan' => $lastOrder + $idx + 1,
                        'created_by' => $firstUserId,
                    ]);
                }
                $rabItems = RabItem::where('rab_id', $rab->id)->orderBy('id')->get();
            }

            $selectedItems = $rabItems->take(3)->values();
            foreach ($selectedItems as $item) {
                $newVolume = max((float) ($item->volume ?? 0), 30);
                $newPrice = (float) ($item->harga_satuan ?? 0);
                if ($newPrice <= 0) {
                    $newPrice = max(1, (float) ($item->data->harga ?? 10000));
                }
                $item->update([
                    'volume' => $newVolume,
                    'harga_satuan' => $newPrice,
                    'satuan' => $item->satuan ?: ($item->data->satuan ?? 'unit'),
                    'created_by' => $item->created_by ?? $firstUserId,
                ]);
            }
            $selectedItems = RabItem::whereIn('id', $selectedItems->pluck('id'))->with('data')->orderBy('id')->get()->values();

            $rab->status = 'approved';
            $rab->submitted_at = $today->copy()->subDays(15);
            $rab->approved_at = $today->copy()->subDays(14);
            $rab->rejected_at = null;
            $rab->submitted_by = $firstUserId;
            $rab->approved_by = $firstUserId;
            $rab->rejected_by = null;
            $rab->rejected_reason = null;
            $rab->save();
            $rab->updateTotals();

            $qtySpp = [12, 10, 8];
            $qtyPo = [10, 8, 6];
            $qtyLpb = [8, 6, 5];
            $qtyBpg = [5, 4, 3];
            $qtyVoucher = [3, 2, 2];

            $spp = Spp::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'spp_no' => 'SEED-SPP-' . $flowCode . '-01'],
                [
                    'vendor_id' => $vendorA->id,
                    'spp_date' => $today->copy()->subDays(12),
                    'schedule_date' => $today->copy()->subDays(9),
                    'status' => 'approved',
                    'requested_by' => 'Staff Seeder',
                    'approved_by' => 'HO Seeder',
                    'notes' => $seedTag . ' SPP lengkap',
                ]
            );
            SppItem::where('spp_id', $spp->id)->delete();
            foreach ($selectedItems as $idx => $item) {
                SppItem::create([
                    'spp_id' => $spp->id,
                    'rab_item_id' => $item->id,
                    'vendor_id' => $vendorA->id,
                    'item_code_snapshot' => $item->data->kode ?? null,
                    'item_name_snapshot' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'unit_snapshot' => $item->satuan,
                    'qty' => min($qtySpp[$idx], (float) $item->volume),
                    'unit' => $item->satuan,
                    'schedule_date' => $today->copy()->subDays(9),
                    'work_notes' => $seedTag . ' item SPP #' . ($idx + 1),
                ]);
            }
            DB::table('spps')->where('id', $spp->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(11),
                'approved_at' => $today->copy()->subDays(10),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            $comparison = VendorComparison::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'comparison_no' => 'SEED-KOM-' . $flowCode . '-01'],
                [
                    'comparison_date' => $today->copy()->subDays(10),
                    'status' => 'approved',
                    'decision_vendor_id' => $vendorA->id,
                    'final_amount' => 0,
                    'difference_amount' => 0,
                    'notes' => $seedTag . ' Komparasi vendor',
                ]
            );
            VendorComparisonItem::where('vendor_comparison_id', $comparison->id)->delete();
            $komTotal = 0.0;
            foreach ($selectedItems as $idx => $item) {
                $qty = min($qtyPo[$idx], (float) $item->volume);
                $rappUnit = (float) $item->harga_satuan;
                $v1 = round($rappUnit * 0.98, 2);
                $v2 = round($rappUnit * 1.01, 2);
                $v3 = round($rappUnit * 1.03, 2);
                $komTotal += $qty * $v1;

                VendorComparisonItem::create([
                    'vendor_comparison_id' => $comparison->id,
                    'rab_item_id' => $item->id,
                    'item_code_snapshot' => $item->data->kode ?? null,
                    'item_name_snapshot' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'unit_snapshot' => $item->satuan,
                    'qty' => $qty,
                    'unit' => $item->satuan,
                    'rapp_unit_price' => $rappUnit,
                    'rapp_total' => $qty * $rappUnit,
                    'vendor1_id' => $vendorA->id,
                    'vendor1_unit_price' => $v1,
                    'vendor1_total' => $qty * $v1,
                    'vendor2_id' => $vendorB->id,
                    'vendor2_unit_price' => $v2,
                    'vendor2_total' => $qty * $v2,
                    'vendor3_id' => $vendorC->id,
                    'vendor3_unit_price' => $v3,
                    'vendor3_total' => $qty * $v3,
                ]);
            }
            $rappRefTotal = 0.0;
            foreach ($selectedItems as $idx => $item) {
                $rappRefTotal += min($qtyPo[$idx], (float) $item->volume) * (float) $item->harga_satuan;
            }
            $comparison->update([
                'final_amount' => $komTotal,
                'difference_amount' => $komTotal - $rappRefTotal,
            ]);
            DB::table('vendor_comparisons')->where('id', $comparison->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(9),
                'approved_at' => $today->copy()->subDays(8),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            $spk = Spk::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'spk_no' => 'SEED-SPK-' . $flowCode . '-01'],
                [
                    'vendor_id' => $vendorA->id,
                    'vendor_comparison_id' => $comparison->id,
                    'spk_date' => $today->copy()->subDays(8),
                    'start_date' => $today->copy()->subDays(7),
                    'end_date' => $today->copy()->addDays(15),
                    'status' => 'approved',
                    'scope' => 'Pekerjaan pengadaan material tahap 1',
                    'total_amount' => 0,
                    'notes' => $seedTag . ' SPK hasil komparasi',
                ]
            );
            SpkItem::where('spk_id', $spk->id)->delete();
            $spkTotal = 0.0;
            foreach ($selectedItems as $idx => $item) {
                $qty = min($qtyPo[$idx], (float) $item->volume);
                $price = round((float) $item->harga_satuan * 0.98, 2);
                $lineTotal = $qty * $price;
                $spkTotal += $lineTotal;
                SpkItem::create([
                    'spk_id' => $spk->id,
                    'rab_item_id' => $item->id,
                    'item_code_snapshot' => $item->data->kode ?? null,
                    'item_name_snapshot' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'unit_snapshot' => $item->satuan,
                    'description' => 'Item SPK ' . ($idx + 1),
                    'qty' => $qty,
                    'unit' => $item->satuan,
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                ]);
            }
            $spk->update(['total_amount' => $spkTotal]);
            DB::table('spks')->where('id', $spk->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(7),
                'approved_at' => $today->copy()->subDays(6),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            $po = PurchaseOrder::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'po_no' => 'SEED-PO-' . $flowCode . '-01'],
                [
                    'vendor_id' => $vendorA->id,
                    'po_date' => $today->copy()->subDays(6),
                    'contact_person' => 'PIC Vendor Seed',
                    'phone' => '081234000001',
                    'address' => $project->location ?? 'Lokasi Proyek',
                    'status' => 'approved',
                    'subtotal_amount' => 0,
                    'tax_percent' => 11,
                    'tax_amount' => 0,
                    'shipping_cost' => 250000,
                    'total_amount' => 0,
                    'notes' => $seedTag . ' PO approved',
                ]
            );
            PurchaseOrderItem::where('purchase_order_id', $po->id)->delete();
            $poSubtotal = 0.0;
            foreach ($selectedItems as $idx => $item) {
                $qty = min($qtyPo[$idx], (float) $item->volume);
                $price = round((float) $item->harga_satuan * 0.98, 2);
                $lineTotal = $qty * $price;
                $poSubtotal += $lineTotal;
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'rab_item_id' => $item->id,
                    'item_code_snapshot' => $item->data->kode ?? null,
                    'item_name_snapshot' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'unit_snapshot' => $item->satuan,
                    'specification' => 'Spec seed',
                    'qty' => $qty,
                    'unit' => $item->satuan,
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                ]);
            }
            $poTax = $poSubtotal * 0.11;
            $poTotal = $poSubtotal + $poTax + 250000;
            $po->update([
                'subtotal_amount' => $poSubtotal,
                'tax_amount' => $poTax,
                'total_amount' => $poTotal,
            ]);
            DB::table('purchase_orders')->where('id', $po->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(5),
                'approved_at' => $today->copy()->subDays(4),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            $lpb = Lpb::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'lpb_no' => 'SEED-LPB-' . $flowCode . '-01'],
                [
                    'vendor_id' => $vendorA->id,
                    'purchase_order_id' => $po->id,
                    'lpb_date' => $today->copy()->subDays(4),
                    'status' => 'approved',
                    'delivered_by' => 'Driver Vendor',
                    'received_by' => 'Staff Gudang',
                    'known_by' => 'HO Seed',
                    'notes' => $seedTag . ' LPB approved',
                ]
            );
            LpbItem::where('lpb_id', $lpb->id)->delete();
            foreach ($selectedItems as $idx => $item) {
                LpbItem::create([
                    'lpb_id' => $lpb->id,
                    'rab_item_id' => $item->id,
                    'item_code_snapshot' => $item->data->kode ?? null,
                    'item_name_snapshot' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'unit_snapshot' => $item->satuan,
                    'qty' => min($qtyLpb[$idx], $qtyPo[$idx]),
                    'unit' => $item->satuan,
                    'arrival_date' => $today->copy()->subDays(4),
                    'doc_reference' => $po->po_no,
                    'work_notes' => $seedTag . ' penerimaan',
                ]);
            }
            DB::table('lpbs')->where('id', $lpb->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(4),
                'approved_at' => $today->copy()->subDays(3),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            $bpg = Bpg::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'bpg_no' => 'SEED-BPG-' . $flowCode . '-01'],
                [
                    'lpb_id' => $lpb->id,
                    'bpg_date' => $today->copy()->subDays(3),
                    'status' => 'approved',
                    'requested_by' => 'Site Supervisor',
                    'approved_by' => 'HO Seed',
                    'known_by' => 'PM Seed',
                    'notes' => $seedTag . ' BPG approved',
                ]
            );
            BpgItem::where('bpg_id', $bpg->id)->delete();
            foreach ($selectedItems as $idx => $item) {
                BpgItem::create([
                    'bpg_id' => $bpg->id,
                    'rab_item_id' => $item->id,
                    'item_code_snapshot' => $item->data->kode ?? null,
                    'item_name_snapshot' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'unit_snapshot' => $item->satuan,
                    'qty' => min($qtyBpg[$idx], $qtyLpb[$idx]),
                    'unit' => $item->satuan,
                    'issue_date' => $today->copy()->subDays(3),
                    'work_notes' => $seedTag . ' pengeluaran',
                ]);
            }
            DB::table('bpgs')->where('id', $bpg->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(3),
                'approved_at' => $today->copy()->subDays(2),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            $voucher = PurchaseVoucher::updateOrCreate(
                ['project_id' => $project->id, 'rab_id' => $rab->id, 'voucher_no' => 'SEED-VCH-' . $flowCode . '-01'],
                [
                    'vendor_id' => $vendorA->id,
                    'purchase_order_id' => $po->id,
                    'lpb_id' => $lpb->id,
                    'project_code_snapshot' => $project->code,
                    'project_name_snapshot' => $project->name,
                    'voucher_date' => $today->copy()->subDays(2),
                    'vendor_name' => $vendorA->nama,
                    'bank' => $vendorA->bank,
                    'account_no' => $vendorA->no_rekening,
                    'account_name' => $vendorA->nama_rekening,
                    'transfer_to' => $vendorA->nama_rekening,
                    'payment_method' => 'transfer',
                    'invoice_no' => 'INV-SEED-' . $flowCode . '-01',
                    'payment_purpose' => 'Pembayaran material tahap 1',
                    'notes' => $seedTag . ' Voucher pembelian',
                    'status' => 'approved',
                    'tax_percent' => 11,
                    'tax_amount' => 0,
                    'shipping_cost' => 0,
                    'subtotal_amount' => 0,
                    'total_amount' => 0,
                    'due_date' => $today->copy()->addDays(7),
                ]
            );
            PurchaseVoucherItem::where('purchase_voucher_id', $voucher->id)->delete();
            $voucherSubtotal = 0.0;
            foreach ($selectedItems as $idx => $item) {
                $qty = min($qtyVoucher[$idx], $qtyLpb[$idx]);
                $price = round((float) $item->harga_satuan * 0.98, 2);
                $amount = $qty * $price;
                $voucherSubtotal += $amount;
                PurchaseVoucherItem::create([
                    'purchase_voucher_id' => $voucher->id,
                    'rab_item_id' => $item->id,
                    'qty' => $qty,
                    'unit' => $item->satuan,
                    'description' => $item->data->uraian ?? $item->keterangan ?? ('RAB Item #' . $item->id),
                    'price' => $price,
                    'amount' => $amount,
                    'rab_harga_satuan_snapshot' => (float) $item->harga_satuan,
                    'rab_volume_snapshot' => (float) $item->volume,
                ]);
            }
            $voucherTax = $voucherSubtotal * 0.11;
            $voucherTotal = $voucherSubtotal + $voucherTax;
            $voucher->update([
                'subtotal_amount' => $voucherSubtotal,
                'tax_amount' => $voucherTax,
                'total_amount' => $voucherTotal,
                'status' => 'approved',
            ]);
            DB::table('purchase_vouchers')->where('id', $voucher->id)->update([
                'status' => 'approved',
                'submitted_at' => $today->copy()->subDays(2),
                'approved_at' => $today->copy()->subDay(),
                'rejected_at' => null,
                'submitted_by' => $firstUserName,
                'approved_by' => 'HO Seeder',
                'rejected_by' => null,
                'rejected_reason' => null,
                'updated_at' => now(),
            ]);

            StockMovement::where('doc_type', 'lpb')->where('doc_id', $lpb->id)->delete();
            foreach (LpbItem::where('lpb_id', $lpb->id)->get() as $it) {
                StockMovement::create([
                    'project_id' => $project->id,
                    'rab_id' => $rab->id,
                    'rab_item_id' => $it->rab_item_id,
                    'movement_type' => 'in',
                    'doc_type' => 'lpb',
                    'doc_id' => $lpb->id,
                    'movement_date' => $it->arrival_date ?? $lpb->lpb_date,
                    'qty' => (float) $it->qty,
                    'unit' => $it->unit,
                    'notes' => $seedTag . ' stok masuk',
                ]);
            }

            StockMovement::where('doc_type', 'bpg')->where('doc_id', $bpg->id)->delete();
            foreach (BpgItem::where('bpg_id', $bpg->id)->get() as $it) {
                StockMovement::create([
                    'project_id' => $project->id,
                    'rab_id' => $rab->id,
                    'rab_item_id' => $it->rab_item_id,
                    'movement_type' => 'out',
                    'doc_type' => 'bpg',
                    'doc_id' => $bpg->id,
                    'movement_date' => $it->issue_date ?? $bpg->bpg_date,
                    'qty' => (float) $it->qty,
                    'unit' => $it->unit,
                    'notes' => $seedTag . ' stok keluar',
                ]);
            }

            $usedRabItemIds = RabBreakdownItem::whereHas('rabBreakdown', function ($q) use ($project): void {
                $q->where('project_id', $project->id);
            })->pluck('rab_item_id')->filter()->unique();

            $wbsCandidates = RabItem::with('data')
                ->where('rab_id', $rab->id)
                ->whereNotIn('id', $usedRabItemIds)
                ->orderBy('id')
                ->take(2)
                ->get();
            if ($wbsCandidates->isEmpty()) {
                $wbsCandidates = $selectedItems->take(2);
            }

            $wbs1 = RabBreakdown::updateOrCreate(
                ['project_id' => $project->id, 'rab_breakdown_code' => 'RAB-SEED-' . $flowCode . '-01'],
                [
                    'name' => 'RAB Breakdown Seed Persiapan & Struktur',
                    'order_number' => 901,
                    'description' => $seedTag . ' RAB Breakdown otomatis 1',
                    'status' => 'in_progress',
                    'approval_status' => 'approved',
                    'submitted_at' => $today->copy()->subDays(6),
                    'approved_at' => $today->copy()->subDays(5),
                    'submitted_by' => $firstUserId,
                    'approved_by' => $firstUserId,
                    'rejected_at' => null,
                    'rejected_by' => null,
                    'rejected_reason' => null,
                    'start_date' => $today->copy()->subDays(10),
                    'end_date' => $today->copy()->addDays(25),
                ]
            );
            RabBreakdownBudgetSource::whereIn('rab_breakdown_item_id', RabBreakdownItem::where('rab_breakdown_id', $wbs1->id)->pluck('id'))->delete();
            RabBreakdownItem::where('rab_breakdown_id', $wbs1->id)->delete();
            foreach ($wbsCandidates as $idx => $it) {
                $wbsItem = RabBreakdownItem::create([
                    'rab_breakdown_id' => $wbs1->id,
                    'item_code' => 'A.' . ($idx + 1),
                    'uraian' => $it->data->uraian ?? $it->keterangan ?? ('RAB Item #' . $it->id),
                    'volume_rab' => (float) $it->volume,
                    'satuan' => $it->satuan ?? ($it->data->satuan ?? 'unit'),
                    'unit_price' => (float) $it->harga_satuan,
                    'total_price' => (float) $it->volume * (float) $it->harga_satuan,
                    'rab_item_id' => $it->id,
                    'notes' => $seedTag . ' item RAB Breakdown 1',
                ]);
                if (!empty($it->data?->kode)) {
                    RabBreakdownBudgetSource::create([
                        'rab_breakdown_item_id' => $wbsItem->id,
                        'master_kode' => $it->data->kode,
                        'master_kategori' => $it->data->kode_kategori ?? substr((string) $it->data->kode, 0, 2),
                        'master_uraian' => $it->data->uraian ?? '-',
                        'master_satuan' => $it->data->satuan ?? ($it->satuan ?? 'unit'),
                        'master_harga' => (float) ($it->data->harga ?? $it->harga_satuan),
                        'allocated_volume' => (float) $it->volume,
                        'allocated_amount' => (float) $it->volume * (float) ($it->data->harga ?? $it->harga_satuan),
                        'notes' => $seedTag . ' mapped dari RAPP',
                    ]);
                }
            }
            $wbs1->updateBudget();
            $wbs1->actual_amount = round((float) $wbs1->budget_amount * 0.45, 2);
            $wbs1->calculateProgress();
            $wbs1->save();

            $wbs2 = RabBreakdown::updateOrCreate(
                ['project_id' => $project->id, 'rab_breakdown_code' => 'RAB-SEED-' . $flowCode . '-02'],
                [
                    'name' => 'RAB Breakdown Seed Arsitektur',
                    'order_number' => 902,
                    'description' => $seedTag . ' RAB Breakdown otomatis 2',
                    'status' => 'not_started',
                    'approval_status' => 'approved',
                    'submitted_at' => $today->copy()->subDays(5),
                    'approved_at' => $today->copy()->subDays(4),
                    'submitted_by' => $firstUserId,
                    'approved_by' => $firstUserId,
                    'rejected_at' => null,
                    'rejected_by' => null,
                    'rejected_reason' => null,
                    'start_date' => $today->copy()->addDays(2),
                    'end_date' => $today->copy()->addDays(40),
                ]
            );

            $wbs2ItemSeed = RabItem::with('data')
                ->where('rab_id', $rab->id)
                ->whereNotIn('id', RabBreakdownItem::whereHas('rabBreakdown', function ($q) use ($project): void {
                    $q->where('project_id', $project->id);
                })->pluck('rab_item_id')->filter()->unique())
                ->orderBy('id')
                ->first() ?? $selectedItems->last();

            RabBreakdownBudgetSource::whereIn('rab_breakdown_item_id', RabBreakdownItem::where('rab_breakdown_id', $wbs2->id)->pluck('id'))->delete();
            RabBreakdownItem::where('rab_breakdown_id', $wbs2->id)->delete();
            $wbs2Item = RabBreakdownItem::create([
                'rab_breakdown_id' => $wbs2->id,
                'item_code' => 'B.1',
                'uraian' => $wbs2ItemSeed->data->uraian ?? $wbs2ItemSeed->keterangan ?? ('RAB Item #' . $wbs2ItemSeed->id),
                'volume_rab' => (float) $wbs2ItemSeed->volume,
                'satuan' => $wbs2ItemSeed->satuan ?? ($wbs2ItemSeed->data->satuan ?? 'unit'),
                'unit_price' => (float) $wbs2ItemSeed->harga_satuan,
                'total_price' => (float) $wbs2ItemSeed->volume * (float) $wbs2ItemSeed->harga_satuan,
                'rab_item_id' => $wbs2ItemSeed->id,
                'notes' => $seedTag . ' item RAB Breakdown 2',
            ]);
            if (!empty($wbs2ItemSeed->data?->kode)) {
                RabBreakdownBudgetSource::create([
                    'rab_breakdown_item_id' => $wbs2Item->id,
                    'master_kode' => $wbs2ItemSeed->data->kode,
                    'master_kategori' => $wbs2ItemSeed->data->kode_kategori ?? substr((string) $wbs2ItemSeed->data->kode, 0, 2),
                    'master_uraian' => $wbs2ItemSeed->data->uraian ?? '-',
                    'master_satuan' => $wbs2ItemSeed->data->satuan ?? ($wbs2ItemSeed->satuan ?? 'unit'),
                    'master_harga' => (float) ($wbs2ItemSeed->data->harga ?? $wbs2ItemSeed->harga_satuan),
                    'allocated_volume' => (float) $wbs2ItemSeed->volume,
                    'allocated_amount' => (float) $wbs2ItemSeed->volume * (float) ($wbs2ItemSeed->data->harga ?? $wbs2ItemSeed->harga_satuan),
                    'notes' => $seedTag . ' mapped dari RAPP',
                ]);
            }
            $wbs2->updateBudget();
            $wbs2->actual_amount = round((float) $wbs2->budget_amount * 0.1, 2);
            $wbs2->calculateProgress();
            $wbs2->save();
        });

        $this->command->info('Seeder ' . static::class . ' selesai: SPP, Komparasi, SPK, PO, LPB, BPG, Voucher, RAB Breakdown sudah dibuat/diupdate.');
    }
}



