<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_vouchers', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('rab_id');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'purchase_order_id')) {
                $table->unsignedBigInteger('purchase_order_id')->nullable()->after('vendor_id');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'lpb_id')) {
                $table->unsignedBigInteger('lpb_id')->nullable()->after('purchase_order_id');
            }

            if (!Schema::hasColumn('purchase_vouchers', 'bank')) {
                $table->string('bank', 50)->nullable()->after('vendor_name');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'account_no')) {
                $table->string('account_no', 50)->nullable()->after('bank');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'account_name')) {
                $table->string('account_name', 255)->nullable()->after('account_no');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'transfer_to')) {
                $table->string('transfer_to', 255)->nullable()->after('account_name');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'payment_method')) {
                $table->string('payment_method', 20)->nullable()->after('transfer_to');
            }

            if (!Schema::hasColumn('purchase_vouchers', 'status')) {
                $table->string('status', 20)->default('draft')->after('notes');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'submitted_by')) {
                $table->string('submitted_by', 100)->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'approved_by')) {
                $table->string('approved_by', 100)->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'rejected_by')) {
                $table->string('rejected_by', 100)->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'due_date')) {
                $table->date('due_date')->nullable()->after('rejected_reason');
            }
            if (!Schema::hasColumn('purchase_vouchers', 'shipping_cost')) {
                $table->decimal('shipping_cost', 15, 2)->default(0)->after('tax_amount');
            }
        });

        Schema::table('purchase_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_vouchers', 'vendor_id')) {
                $table->index(['vendor_id']);
            }
            if (Schema::hasColumn('purchase_vouchers', 'purchase_order_id')) {
                $table->index(['purchase_order_id']);
            }
            if (Schema::hasColumn('purchase_vouchers', 'lpb_id')) {
                $table->index(['lpb_id']);
            }
        });

        Schema::table('purchase_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_vouchers', 'vendor_id')) {
                $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            }
            if (Schema::hasColumn('purchase_vouchers', 'purchase_order_id')) {
                $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            }
            if (Schema::hasColumn('purchase_vouchers', 'lpb_id')) {
                $table->foreign('lpb_id')->references('id')->on('lpbs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_vouchers', 'lpb_id')) {
                $table->dropForeign(['lpb_id']);
                $table->dropColumn('lpb_id');
            }
            if (Schema::hasColumn('purchase_vouchers', 'purchase_order_id')) {
                $table->dropForeign(['purchase_order_id']);
                $table->dropColumn('purchase_order_id');
            }
            if (Schema::hasColumn('purchase_vouchers', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }

            $cols = [
                'bank',
                'account_no',
                'account_name',
                'transfer_to',
                'payment_method',
                'status',
                'submitted_at',
                'approved_at',
                'rejected_at',
                'submitted_by',
                'approved_by',
                'rejected_by',
                'rejected_reason',
                'due_date',
                'shipping_cost',
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('purchase_vouchers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
