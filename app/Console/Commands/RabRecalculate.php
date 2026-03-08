<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\RabItem;
use App\Models\PurchaseVoucher;
use App\Models\PurchaseVoucherItem;

class RabRecalculate extends Command
{
    protected $signature = 'rab:recalculate {--rab=} {--project=}';
    protected $description = 'Recalculate RAB realisasi/sisa dan total voucher (Excel logic)';

    public function handle()
    {
        $rabId = $this->option('rab');
        $projectId = $this->option('project');

        $this->info('Recalculate started...');

        DB::transaction(function () use ($rabId, $projectId) {

            // ======================
            // RAB ITEMS
            // ======================
            RabItem::when($rabId, fn($q) => $q->where('rab_id', $rabId))
                ->orderBy('id')
                ->chunk(200, function ($items) {
                    foreach ($items as $item) {
                        $budgetVol = (float) ($item->volume ?? 0);
                        $hargaRab  = (float) ($item->harga_satuan ?? 0);
                        $budgetAmt = $budgetVol * $hargaRab;

                        $realisasiVol = (float) PurchaseVoucherItem::where('rab_item_id', $item->id)->sum('qty');
                        $realisasiAmt = $realisasiVol * $hargaRab;

                        $item->update([
                            'realisasi_volume' => $realisasiVol,
                            'realisasi_amount' => max(0, $realisasiAmt),
                            'sisa_volume'      => max(0, $budgetVol - $realisasiVol),
                            'sisa_amount'      => max(0, $budgetAmt - $realisasiAmt),
                        ]);
                    }
                });

            // ======================
            // PURCHASE VOUCHERS
            // ======================
            PurchaseVoucher::with('items')
                ->when($rabId, fn($q) => $q->where('rab_id', $rabId))
                ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                ->orderBy('id')
                ->chunk(100, function ($vouchers) {
                    foreach ($vouchers as $v) {
                        $subtotal = (float) $v->items->sum('amount');
                        $taxPercent = (float) ($v->tax_percent ?? 0);
                        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
                        $total = $subtotal + $taxAmount;

                        $v->update([
                            'subtotal_amount' => $subtotal,
                            'tax_amount'      => $taxAmount,
                            'total_amount'    => $total,
                        ]);
                    }
                });
        });

        $this->info('Recalculate done.');
        return Command::SUCCESS;
    }
}
