<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1) Rename tables first
        if (Schema::hasTable('work_breakdown_structures') && !Schema::hasTable('rab_breakdowns')) {
            Schema::rename('work_breakdown_structures', 'rab_breakdowns');
        }

        if (Schema::hasTable('wbs_items') && !Schema::hasTable('rab_breakdown_items')) {
            Schema::rename('wbs_items', 'rab_breakdown_items');
        }

        if (Schema::hasTable('wbs_budget_sources') && !Schema::hasTable('rab_breakdown_budget_sources')) {
            Schema::rename('wbs_budget_sources', 'rab_breakdown_budget_sources');
        }

        // 2) Rename columns after tables exist with new names
        if (Schema::hasTable('rab_breakdowns')
            && Schema::hasColumn('rab_breakdowns', 'wbs_code')
            && !Schema::hasColumn('rab_breakdowns', 'rab_breakdown_code')) {
            Schema::table('rab_breakdowns', function (Blueprint $table) {
                $table->renameColumn('wbs_code', 'rab_breakdown_code');
            });
        }

        if (Schema::hasTable('rab_breakdown_items')
            && Schema::hasColumn('rab_breakdown_items', 'wbs_id')
            && !Schema::hasColumn('rab_breakdown_items', 'rab_breakdown_id')) {
            Schema::table('rab_breakdown_items', function (Blueprint $table) {
                $table->renameColumn('wbs_id', 'rab_breakdown_id');
            });
        }

        if (Schema::hasTable('rab_breakdown_budget_sources')
            && Schema::hasColumn('rab_breakdown_budget_sources', 'wbs_item_id')
            && !Schema::hasColumn('rab_breakdown_budget_sources', 'rab_breakdown_item_id')) {
            Schema::table('rab_breakdown_budget_sources', function (Blueprint $table) {
                $table->renameColumn('wbs_item_id', 'rab_breakdown_item_id');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('rab_breakdowns')
            && Schema::hasColumn('rab_breakdowns', 'rab_breakdown_code')
            && !Schema::hasColumn('rab_breakdowns', 'wbs_code')) {
            Schema::table('rab_breakdowns', function (Blueprint $table) {
                $table->renameColumn('rab_breakdown_code', 'wbs_code');
            });
        }

        if (Schema::hasTable('rab_breakdown_items')
            && Schema::hasColumn('rab_breakdown_items', 'rab_breakdown_id')
            && !Schema::hasColumn('rab_breakdown_items', 'wbs_id')) {
            Schema::table('rab_breakdown_items', function (Blueprint $table) {
                $table->renameColumn('rab_breakdown_id', 'wbs_id');
            });
        }

        if (Schema::hasTable('rab_breakdown_budget_sources')
            && Schema::hasColumn('rab_breakdown_budget_sources', 'rab_breakdown_item_id')
            && !Schema::hasColumn('rab_breakdown_budget_sources', 'wbs_item_id')) {
            Schema::table('rab_breakdown_budget_sources', function (Blueprint $table) {
                $table->renameColumn('rab_breakdown_item_id', 'wbs_item_id');
            });
        }

        if (Schema::hasTable('rab_breakdown_budget_sources') && !Schema::hasTable('wbs_budget_sources')) {
            Schema::rename('rab_breakdown_budget_sources', 'wbs_budget_sources');
        }

        if (Schema::hasTable('rab_breakdown_items') && !Schema::hasTable('wbs_items')) {
            Schema::rename('rab_breakdown_items', 'wbs_items');
        }

        if (Schema::hasTable('rab_breakdowns') && !Schema::hasTable('work_breakdown_structures')) {
            Schema::rename('rab_breakdowns', 'work_breakdown_structures');
        }

        Schema::enableForeignKeyConstraints();
    }
};

