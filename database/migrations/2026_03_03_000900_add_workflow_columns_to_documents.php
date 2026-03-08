<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'spps',
            'bpgs',
            'lpbs',
            'purchase_orders',
            'spks',
            'vendor_comparisons',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (!Schema::hasColumn($table, 'submitted_at')) {
                    $blueprint->timestamp('submitted_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn($table, 'approved_at')) {
                    $blueprint->timestamp('approved_at')->nullable()->after('submitted_at');
                }
                if (!Schema::hasColumn($table, 'rejected_at')) {
                    $blueprint->timestamp('rejected_at')->nullable()->after('approved_at');
                }

                if (!Schema::hasColumn($table, 'submitted_by')) {
                    $blueprint->string('submitted_by', 100)->nullable()->after('rejected_at');
                }
                if (!Schema::hasColumn($table, 'approved_by')) {
                    $blueprint->string('approved_by', 100)->nullable()->after('submitted_by');
                }
                if (!Schema::hasColumn($table, 'rejected_by')) {
                    $blueprint->string('rejected_by', 100)->nullable()->after('approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'spps',
            'bpgs',
            'lpbs',
            'purchase_orders',
            'spks',
            'vendor_comparisons',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'submitted_at')) {
                    $blueprint->dropColumn('submitted_at');
                }
                if (Schema::hasColumn($table, 'approved_at')) {
                    $blueprint->dropColumn('approved_at');
                }
                if (Schema::hasColumn($table, 'rejected_at')) {
                    $blueprint->dropColumn('rejected_at');
                }
                if (Schema::hasColumn($table, 'submitted_by')) {
                    $blueprint->dropColumn('submitted_by');
                }
                if (Schema::hasColumn($table, 'approved_by')) {
                    $blueprint->dropColumn('approved_by');
                }
                if (Schema::hasColumn($table, 'rejected_by')) {
                    $blueprint->dropColumn('rejected_by');
                }
            });
        }
    }
};
