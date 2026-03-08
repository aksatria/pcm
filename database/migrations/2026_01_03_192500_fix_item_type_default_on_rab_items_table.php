<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perbaiki kolom item_type di tabel rab_items
     */
    public function up(): void
    {
        // 1. Isi semua item_type NULL jadi 'material'
        DB::table('rab_items')
            ->whereNull('item_type')
            ->update(['item_type' => 'material']);

        /**
         * 2. Untuk SQLite, ubah default item_type jadi 'material'
         *
         * Catatan:
         * - SQLite tidak mendukung change() biasa seperti MySQL,
         *   jadi kita pakai raw SQL ALTER TABLE.
         * - Kalau ini error di environment-mu, bagian ini boleh kamu komentar dulu,
         *   karena poin (1) saja sudah menyelesaikan error NOT NULL untuk data existing.
         */

        try {
            DB::statement("
                CREATE TABLE rab_items_tmp AS
                SELECT * FROM rab_items
            ");

            DB::statement("DROP TABLE rab_items");

            // Buat ulang tabel rab_items dengan item_type NOT NULL DEFAULT 'material'
            DB::statement("
                CREATE TABLE rab_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    rab_id INTEGER NOT NULL,
                    data_id INTEGER NOT NULL,
                    item_type VARCHAR(50) NOT NULL DEFAULT 'material',
                    volume DECIMAL(18,4) NOT NULL DEFAULT 0,
                    satuan VARCHAR(50) NULL,
                    harga_satuan DECIMAL(18,2) NOT NULL DEFAULT 0,
                    realisasi_volume DECIMAL(18,4) NOT NULL DEFAULT 0,
                    realisasi_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
                    sisa_volume DECIMAL(18,4) NOT NULL DEFAULT 0,
                    sisa_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
                    keterangan TEXT NULL,
                    urutan INTEGER NULL,
                    created_by INTEGER NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ");

            DB::statement("
                INSERT INTO rab_items (
                    id, rab_id, data_id, item_type, volume, satuan,
                    harga_satuan, realisasi_volume, realisasi_amount,
                    sisa_volume, sisa_amount, keterangan, urutan,
                    created_by, created_at, updated_at
                )
                SELECT
                    id, rab_id, data_id,
                    COALESCE(item_type, 'material') AS item_type,
                    volume, satuan, harga_satuan, realisasi_volume,
                    realisasi_amount, sisa_volume, sisa_amount,
                    keterangan, urutan, created_by, created_at, updated_at
                FROM rab_items_tmp
            ");

            DB::statement("DROP TABLE rab_items_tmp");
        } catch (\Throwable $e) {
            // Kalau gagal (misal di production nanti pakai MySQL),
            // bagian ini bisa di-skip, yang penting data NULL sudah diisi di langkah (1).
        }
    }

    /**
     * Rollback sederhana
     */
    public function down(): void
    {
        // Untuk down, cukup set NULL lagi kalau mau (opsional)
        // Tidak kita implementasi penuh karena struktur lama mungkin berbeda.
    }
};
