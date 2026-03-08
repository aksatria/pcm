<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('work_breakdown_structures')) {
            return;
        }

        Schema::table('work_breakdown_structures', function (Blueprint $table) {
            if (!Schema::hasColumn('work_breakdown_structures', 'approval_status')) {
                $table->string('approval_status', 20)->default('draft')->after('status');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('approval_status');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('work_breakdown_structures', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('rejected_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('work_breakdown_structures')) {
            return;
        }

        Schema::table('work_breakdown_structures', function (Blueprint $table) {
            $columns = [
                'approval_status',
                'submitted_at',
                'approved_at',
                'rejected_at',
                'submitted_by',
                'approved_by',
                'rejected_by',
                'rejected_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('work_breakdown_structures', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

