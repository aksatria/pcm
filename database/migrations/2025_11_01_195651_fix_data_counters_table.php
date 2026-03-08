<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Cek apakah tabel data_counters ada tapi kosong atau bermasalah
        if (Schema::hasTable('data_counters')) {
            // Jika tabel ada tapi kosong, isi data
            if (DB::table('data_counters')->count() == 0) {
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
        } else {
            // Jika tabel tidak ada, buat baru
            Schema::create('data_counters', function (Blueprint $table) {
                $table->string('kode_kategori')->primary();
                $table->unsignedInteger('terakhir')->default(0);
                $table->timestamps();
            });

            // Insert data awal
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

        // Cek dan perbaiki tabel data jika perlu
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
        // Jangan drop tabel, biarkan data tetap ada
        // Schema::dropIfExists('data_counters');
    }
};