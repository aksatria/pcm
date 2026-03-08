<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['data', 'clients', 'vendors'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'delete_status')) {
                    $table->string('delete_status', 20)->default('none')->index();
                }
                if (!Schema::hasColumn($table->getTable(), 'delete_requested_by')) {
                    $table->string('delete_requested_by')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'delete_requested_at')) {
                    $table->timestamp('delete_requested_at')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'delete_reason')) {
                    $table->text('delete_reason')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'delete_reviewed_by')) {
                    $table->string('delete_reviewed_by')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'delete_reviewed_at')) {
                    $table->timestamp('delete_reviewed_at')->nullable();
                }
                if (!Schema::hasColumn($table->getTable(), 'delete_review_note')) {
                    $table->text('delete_review_note')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['data', 'clients', 'vendors'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) {
                foreach ([
                    'delete_status',
                    'delete_requested_by',
                    'delete_requested_at',
                    'delete_reason',
                    'delete_reviewed_by',
                    'delete_reviewed_at',
                    'delete_review_note',
                ] as $column) {
                    if (Schema::hasColumn($table->getTable(), $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
