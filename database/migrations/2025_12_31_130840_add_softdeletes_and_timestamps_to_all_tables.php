<?php
// database/migrations/[timestamp]_add_soft_deletes_to_all_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Budget Controls
        if (Schema::hasTable('budget_controls')) {
            Schema::table('budget_controls', function (Blueprint $table) {
                if (!Schema::hasColumn('budget_controls', 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn('budget_controls', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Vendors
        if (Schema::hasTable('vendors')) {
            Schema::table('vendors', function (Blueprint $table) {
                if (!Schema::hasColumn('vendors', 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn('vendors', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Vouchers
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('vouchers', 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn('vouchers', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Voucher Items
        if (Schema::hasTable('voucher_items')) {
            Schema::table('voucher_items', function (Blueprint $table) {
                if (!Schema::hasColumn('voucher_items', 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn('voucher_items', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        // Budget Controls
        Schema::table('budget_controls', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Vouchers
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Voucher Items
        Schema::table('voucher_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};