<?php
// database/migrations/2025_11_01_173831_create_data_counters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('data_counters')) {
            Schema::create('data_counters', function (Blueprint $table) {
                $table->string('kode_kategori')->primary()->comment('Kategori: MT, JS, AT, HO, SR, SB');
                $table->unsignedInteger('terakhir')->default(0)->comment('Nomor terakhir yang digunakan');
                $table->string('created_by')->default('system');
                $table->string('updated_by')->default('system');
                $table->timestamps();

                // Indexes
                $table->index('kode_kategori');
                $table->index('terakhir');
            });
        }

        // Insert initial data untuk semua kategori
        $categories = [
            ['kode_kategori' => 'MT', 'terakhir' => 0, 'created_by' => 'system', 'updated_by' => 'system'],
            ['kode_kategori' => 'JS', 'terakhir' => 0, 'created_by' => 'system', 'updated_by' => 'system'],
            ['kode_kategori' => 'AT', 'terakhir' => 0, 'created_by' => 'system', 'updated_by' => 'system'],
            ['kode_kategori' => 'HO', 'terakhir' => 0, 'created_by' => 'system', 'updated_by' => 'system'],
            ['kode_kategori' => 'SR', 'terakhir' => 0, 'created_by' => 'system', 'updated_by' => 'system'],
            ['kode_kategori' => 'SB', 'terakhir' => 0, 'created_by' => 'system', 'updated_by' => 'system'],
        ];

        foreach ($categories as $category) {
            DB::table('data_counters')->updateOrInsert(
                ['kode_kategori' => $category['kode_kategori']],
                $category
            );
        }

        // Sync dengan data yang sudah ada (jika ada)
        $this->syncExistingCounters();
    }

    /**
     * Sync counter dengan data yang sudah ada di tabel data
     */
    private function syncExistingCounters()
    {
        try {
            // Ambil nomor terbesar untuk setiap kategori dari data
            $counters = DB::table('data')
                ->select('kode_kategori', DB::raw('MAX(CAST(SUBSTRING(kode, 4) AS UNSIGNED)) as max_number'))
                ->where('kode', 'REGEXP', '^[A-Z]{2}-[0-9]+$')
                ->groupBy('kode_kategori')
                ->get();

            foreach ($counters as $counter) {
                if ($counter->max_number > 0) {
                    DB::table('data_counters')
                        ->where('kode_kategori', $counter->kode_kategori)
                        ->update(['terakhir' => $counter->max_number]);
                }
            }
        } catch (\Exception $e) {
            // Jika ada error, skip saja - table data mungkin belum ada
            \Log::info('Sync counters skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('data_counters');
    }
};
