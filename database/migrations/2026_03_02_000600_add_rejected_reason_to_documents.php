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
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'rejected_reason')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->text('rejected_reason')->nullable()->after('status');
                });
            }
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
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'rejected_reason')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('rejected_reason');
                });
            }
        }
    }
};
