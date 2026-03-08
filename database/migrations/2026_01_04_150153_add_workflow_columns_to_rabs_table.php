<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom workflow (submit/approve/reject) ke tabel rabs.
     */
    public function up(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            // Kolom tanggal workflow
            if (!Schema::hasColumn('rabs', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('breakdown');
            }

            if (!Schema::hasColumn('rabs', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('rabs', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }

            // Kolom user workflow
            if (!Schema::hasColumn('rabs', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('rabs', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_by');
            }

            if (!Schema::hasColumn('rabs', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Rollback: hapus kolom-kolom workflow (kalau ada).
     */
    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            if (Schema::hasColumn('rabs', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('rabs', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('rabs', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('rabs', 'submitted_by')) {
                $table->dropColumn('submitted_by');
            }
            if (Schema::hasColumn('rabs', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('rabs', 'rejected_by')) {
                $table->dropColumn('rejected_by');
            }
        });
    }
};
