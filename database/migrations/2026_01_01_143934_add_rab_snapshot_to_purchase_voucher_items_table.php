<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_voucher_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_voucher_items', 'rab_harga_satuan_snapshot')) {
                $table->decimal('rab_harga_satuan_snapshot', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('purchase_voucher_items', 'rab_volume_snapshot')) {
                $table->decimal('rab_volume_snapshot', 15, 2)->default(0)->after('rab_harga_satuan_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_voucher_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_voucher_items', 'rab_harga_satuan_snapshot')) {
                $table->dropColumn('rab_harga_satuan_snapshot');
            }
            if (Schema::hasColumn('purchase_voucher_items', 'rab_volume_snapshot')) {
                $table->dropColumn('rab_volume_snapshot');
            }
        });
    }
};
