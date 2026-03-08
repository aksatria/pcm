<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom-kolom meta yang dibutuhkan model Rab / RappController
     */
    public function up(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            // Deskripsi RAPP
            if (!Schema::hasColumn('rabs', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            // Status (kalau ternyata belum ada)
            if (!Schema::hasColumn('rabs', 'status')) {
                $table->string('status')->default('draft')->after('version');
            }

            // Total budget RAPP (kalau belum ada)
            if (!Schema::hasColumn('rabs', 'total_budget')) {
                $table->decimal('total_budget', 18, 2)->default(0)->after('status');
            }

            // Flag aktif (hanya 1 RAPP aktif per project)
            if (!Schema::hasColumn('rabs', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('total_budget');
            }

            // JSON breakdown per kategori (untuk summary / chart)
            if (!Schema::hasColumn('rabs', 'breakdown')) {
                $table->json('breakdown')->nullable()->after('is_active');
            }

            // Waktu submit / approve / reject
            if (!Schema::hasColumn('rabs', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('breakdown');
            }

            if (!Schema::hasColumn('rabs', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('rabs', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }

            // Alasan reject
            if (!Schema::hasColumn('rabs', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('rejected_at');
            }

            // Catatan tambahan
            if (!Schema::hasColumn('rabs', 'notes')) {
                $table->text('notes')->nullable()->after('rejected_reason');
            }

            // User pembuat RAPP
            if (!Schema::hasColumn('rabs', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            }
        });
    }

    /**
     * Rollback: hapus kolom-kolom meta (kalau ada)
     */
    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            if (Schema::hasColumn('rabs', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('rabs', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('rabs', 'total_budget')) {
                $table->dropColumn('total_budget');
            }
            if (Schema::hasColumn('rabs', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('rabs', 'breakdown')) {
                $table->dropColumn('breakdown');
            }
            if (Schema::hasColumn('rabs', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('rabs', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('rabs', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('rabs', 'rejected_reason')) {
                $table->dropColumn('rejected_reason');
            }
            if (Schema::hasColumn('rabs', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('rabs', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
