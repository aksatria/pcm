<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom created_by ke tabel rab_items
     */
    public function up(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {
            // Cek dulu biar aman kalau suatu saat migrate ulang
            if (!Schema::hasColumn('rab_items', 'created_by')) {
                // Kalau di project lain kamu pakai unsignedBigInteger juga, samakan
                $table->unsignedBigInteger('created_by')
                      ->nullable()
                      ->after('harga_satuan');
            }
        });
    }

    /**
     * Rollback: hapus kolom created_by (kalau ada)
     */
    public function down(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {
            if (Schema::hasColumn('rab_items', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
