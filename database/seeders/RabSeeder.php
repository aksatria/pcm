<?php
// database/seeders/RabSeeder.php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Rab;
use App\Models\Data;
use Illuminate\Database\Seeder;

class RabSeeder extends Seeder
{
    public function run()
    {
        $project = Project::first();
        
        if ($project) {
            $rab = Rab::create([
                'project_id' => $project->id,
                'name' => 'RAB Utama Project ' . $project->name,
                'version' => '1.0',
                'status' => 'draft',
                'notes' => 'Rencana anggaran biaya utama'
            ]);

            // Ambil beberapa data master untuk testing
            $materialItems = Data::where('kode_kategori', 'MT')->limit(5)->get();
            $jasaItems = Data::where('kode_kategori', 'JS')->limit(3)->get();

            foreach ($materialItems as $item) {
                $rab->items()->create([
                    'data_id' => $item->id,
                    'item_type' => $item->kode_kategori,
                    'volume' => rand(10, 100),
                    'satuan' => $item->satuan,
                    'harga_satuan' => $item->harga,
                    'keterangan' => 'Item material'
                ]);
            }

            foreach ($jasaItems as $item) {
                $rab->items()->create([
                    'data_id' => $item->id,
                    'item_type' => $item->kode_kategori,
                    'volume' => rand(1, 5),
                    'satuan' => $item->satuan,
                    'harga_satuan' => $item->harga,
                    'keterangan' => 'Item jasa'
                ]);
            }

            $rab->updateTotals();
        }
    }
}