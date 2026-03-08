<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_voucher_items', function (Blueprint $table) {
            try { $table->index(['rab_item_id'], 'pvi_rab_item_id_idx'); } catch (\Throwable $e) {}
            try { $table->index(['purchase_voucher_id'], 'pvi_purchase_voucher_id_idx'); } catch (\Throwable $e) {}
        });

        Schema::table('purchase_vouchers', function (Blueprint $table) {
            try { $table->index(['project_id', 'rab_id'], 'pv_project_rab_idx'); } catch (\Throwable $e) {}
            try { $table->index(['rab_id', 'voucher_date'], 'pv_rab_date_idx'); } catch (\Throwable $e) {}
            try { $table->index(['voucher_no'], 'pv_voucher_no_idx'); } catch (\Throwable $e) {}
        });

        Schema::table('rab_items', function (Blueprint $table) {
            try { $table->index(['rab_id'], 'rab_items_rab_id_idx'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('purchase_voucher_items', function (Blueprint $table) {
            try { $table->dropIndex('pvi_rab_item_id_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('pvi_purchase_voucher_id_idx'); } catch (\Throwable $e) {}
        });

        Schema::table('purchase_vouchers', function (Blueprint $table) {
            try { $table->dropIndex('pv_project_rab_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('pv_rab_date_idx'); } catch (\Throwable $e) {}
            try { $table->dropIndex('pv_voucher_no_idx'); } catch (\Throwable $e) {}
        });

        Schema::table('rab_items', function (Blueprint $table) {
            try { $table->dropIndex('rab_items_rab_id_idx'); } catch (\Throwable $e) {}
        });
    }
};
