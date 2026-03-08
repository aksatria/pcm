<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom rejected_reason ke tabel rabs.
     */
    public function up(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            if (!Schema::hasColumn('rabs', 'rejected_reason')) {
                // text karena alasan revisi biasanya bisa panjang
                $table->text('rejected_reason')->nullable()->after('rejected_by');
            }
        });
    }

    /**
     * Rollback: hapus kolom rejected_reason (kalau ada).
     */
    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            if (Schema::hasColumn('rabs', 'rejected_reason')) {
                $table->dropColumn('rejected_reason');
            }
        });
    }
};
