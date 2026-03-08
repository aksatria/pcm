<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_vouchers', function (Blueprint $table) {
            // --- Info tambahan voucher ---
            $table->string('invoice_no', 100)->nullable()->after('vendor_name');
            $table->string('payment_purpose', 255)->nullable()->after('invoice_no');

            // --- Snapshot proyek (audit trail) ---
            $table->string('project_code_snapshot', 100)->nullable()->after('project_id');
            $table->string('project_name_snapshot', 255)->nullable()->after('project_code_snapshot');

            // --- PPN + total ---
            $table->decimal('tax_percent', 5, 2)->default(0)->after('notes');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_percent');
            $table->decimal('subtotal_amount', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('total_amount', 15, 2)->default(0)->after('subtotal_amount');

            // Index opsional
            $table->index(['voucher_date']);
            $table->index(['voucher_no']);
            $table->index(['invoice_no']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_vouchers', function (Blueprint $table) {
            $table->dropIndex(['voucher_date']);
            $table->dropIndex(['voucher_no']);
            $table->dropIndex(['invoice_no']);

            $table->dropColumn([
                'invoice_no',
                'payment_purpose',
                'project_code_snapshot',
                'project_name_snapshot',
                'tax_percent',
                'tax_amount',
                'subtotal_amount',
                'total_amount',
            ]);
        });
    }
};
