<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {

            if (!Schema::hasColumn('rab_items', 'realisasi_volume')) {
                $table->decimal('realisasi_volume', 15, 2)
                      ->default(0)
                      ->after('volume');
            }

            if (!Schema::hasColumn('rab_items', 'realisasi_amount')) {
                $table->decimal('realisasi_amount', 18, 2)
                      ->default(0)
                      ->after('realisasi_volume');
            }

            if (!Schema::hasColumn('rab_items', 'sisa_volume')) {
                $table->decimal('sisa_volume', 15, 2)
                      ->default(0)
                      ->after('realisasi_amount');
            }

            if (!Schema::hasColumn('rab_items', 'sisa_amount')) {
                $table->decimal('sisa_amount', 18, 2)
                      ->default(0)
                      ->after('sisa_volume');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rab_items', function (Blueprint $table) {

            if (Schema::hasColumn('rab_items', 'realisasi_volume')) {
                $table->dropColumn('realisasi_volume');
            }

            if (Schema::hasColumn('rab_items', 'realisasi_amount')) {
                $table->dropColumn('realisasi_amount');
            }

            if (Schema::hasColumn('rab_items', 'sisa_volume')) {
                $table->dropColumn('sisa_volume');
            }

            if (Schema::hasColumn('rab_items', 'sisa_amount')) {
                $table->dropColumn('sisa_amount');
            }
        });
    }
};
