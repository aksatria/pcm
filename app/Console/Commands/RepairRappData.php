<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rab;
use Illuminate\Support\Facades\Log;

class RepairRappData extends Command
{
    protected $signature = 'rapp:repair 
                            {--rab= : ID RAPP spesifik}
                            {--all : Repair semua RAPP}
                            {--dry-run : Hanya tampilkan, tidak eksekusi}';
    
    protected $description = 'Repair inconsistent RAPP data';

    public function handle()
    {
        $query = Rab::query();
        
        if ($this->option('rab')) {
            $query->where('id', $this->option('rab'));
            $this->info("Memproses RAPP ID: " . $this->option('rab'));
        } elseif ($this->option('all')) {
            $this->info("Memproses SEMUA RAPP...");
        } else {
            $this->error("Pilih opsi: --rab=ID atau --all");
            return 1;
        }
        
        $dryRun = $this->option('dry-run');
        $count = 0;
        $repaired = 0;
        
        $query->chunk(50, function ($rabs) use (&$count, &$repaired, $dryRun) {
            foreach ($rabs as $rab) {
                $count++;
                
                // Hitung ulang total dari items
                $calculatedTotal = $rab->items()->sum(
                    \Illuminate\Support\Facades\DB::raw('COALESCE(volume, 0) * COALESCE(harga_satuan, 0)')
                );
                
                $calculatedTotal = (float) $calculatedTotal;
                $storedTotal = (float) $rab->total_budget;
                $difference = abs($storedTotal - $calculatedTotal);
                
                // Jika berbeda > 1 (toleransi rounding)
                if ($difference > 1) {
                    $this->info("RAPP {$rab->id} ({$rab->name}):");
                    $this->line("  Stored: Rp " . number_format($storedTotal, 0, ',', '.'));
                    $this->line("  Calculated: Rp " . number_format($calculatedTotal, 0, ',', '.'));
                    $this->line("  Difference: Rp " . number_format($difference, 0, ',', '.'));
                    
                    if (!$dryRun) {
                        $rab->total_budget = $calculatedTotal;
                        $rab->breakdown = $rab->getBreakdownByCategory();
                        $rab->save();
                        
                        $this->info("  ✅ REPAIRED");
                        $repaired++;
                        
                        Log::info('RAPP Data Repaired by Command', [
                            'rab_id' => $rab->id,
                            'old_total' => $storedTotal,
                            'new_total' => $calculatedTotal,
                            'difference' => $calculatedTotal - $storedTotal
                        ]);
                    } else {
                        $this->info("  ⚠ (Dry run - tidak disimpan)");
                    }
                }
            }
        });
        
        $this->newLine();
        $this->info("✅ Selesai!");
        $this->line("Total RAPP diproses: {$count}");
        $this->line("Total RAPP direpair: {$repaired}");
        
        if ($dryRun) {
            $this->warn("⚠ MODE DRY RUN - Tidak ada perubahan ke database");
        }
        
        return 0;
    }
}