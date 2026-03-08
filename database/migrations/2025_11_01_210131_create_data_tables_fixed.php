<?php
// database/migrations/2025_11_01_200000_create_data_tables_fixed.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hapus tabel lama jika ada
        Schema::dropIfExists('data');
        Schema::dropIfExists('data_counters');

        // Tabel data_counters untuk menyimpan counter per kategori
        Schema::create('data_counters', function (Blueprint $table) {
            $table->string('kode_kategori')->primary();
            $table->unsignedInteger('terakhir')->default(0);
            $table->timestamps();
        });

        // Insert data awal dengan nilai 0
        $counters = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
        foreach ($counters as $counter) {
            DB::table('data_counters')->insert([
                'kode_kategori' => $counter,
                'terakhir' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Tabel data utama TANPA SOFT DELETE
        Schema::create('data', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('kategori');
            $table->string('kode_kategori');
            $table->text('uraian');
            $table->string('satuan');
            $table->decimal('harga', 15, 2);
            $table->boolean('status')->default(true);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            // TIDAK ADA softDeletes() - TANPA SOFT DELETE
        });
    }

    public function down()
    {
        Schema::dropIfExists('data');
        Schema::dropIfExists('data_counters');
    }
};