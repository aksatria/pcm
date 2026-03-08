<?php
// database/migrations/2025_11_01_180000_create_data_tables_simple.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Buat tabel data_counters dulu
        if (!Schema::hasTable('data_counters')) {
            Schema::create('data_counters', function (Blueprint $table) {
                $table->string('kode_kategori')->primary();
                $table->unsignedInteger('terakhir')->default(0);
                $table->timestamps();
            });

            // Insert initial counters
            $counters = ['MT', 'JS', 'AT', 'HO', 'SR', 'SB'];
            foreach ($counters as $counter) {
                DB::table('data_counters')->insert([
                    'kode_kategori' => $counter,
                    'terakhir' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 2. Buat tabel data
        if (!Schema::hasTable('data')) {
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
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('data');
        Schema::dropIfExists('data_counters');
    }
};