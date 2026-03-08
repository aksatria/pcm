<?php
// database/migrations/2024_01_01_000001_create_data_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hanya buat tabel data_counters jika belum ada
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
                DB::table('data_counters')->insert($category);
            }
        }

        // Buat tabel data (utama)
        if (!Schema::hasTable('data')) {
            Schema::create('data', function (Blueprint $table) {
                $table->id();
                $table->string('kode')->unique()->comment('Format: JS-001, MT-001');
                $table->string('kategori')->comment('Alat, Jasa, Material, dll');
                $table->string('kode_kategori')->comment('JS, MT, AT, HO, SR, SB');
                $table->text('uraian');
                $table->string('satuan');
                $table->decimal('harga', 15, 2);
                $table->boolean('status')->default(true);
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['kode_kategori', 'kategori']);
                $table->index('status');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('data');
        Schema::dropIfExists('data_counters');
    }
};