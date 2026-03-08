<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_voucher_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_voucher_items', 'unit')) {
                $table->string('unit', 50)->nullable()->after('qty');
            }
            if (!Schema::hasColumn('purchase_voucher_items', 'description')) {
                $table->string('description', 255)->nullable()->after('unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_voucher_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_voucher_items', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('purchase_voucher_items', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
