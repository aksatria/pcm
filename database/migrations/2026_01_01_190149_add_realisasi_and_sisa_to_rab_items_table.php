<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {
            // REALISASI
            if (!Schema::hasColumn('rab_items', 'realisasi_volume')) {
                $table->decimal('realisasi_volume', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('rab_items', 'realisasi_amount')) {
                $table->decimal('realisasi_amount', 15, 2)->default(0);
            }

            // SISA
            if (!Schema::hasColumn('rab_items', 'sisa_volume')) {
                $table->decimal('sisa_volume', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('rab_items', 'sisa_amount')) {
                $table->decimal('sisa_amount', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {
            $columns = [
                'realisasi_volume',
                'realisasi_amount',
                'sisa_volume',
                'sisa_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('rab_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
