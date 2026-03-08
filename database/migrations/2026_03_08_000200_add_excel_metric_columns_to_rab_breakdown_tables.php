<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnsIfTableExists('rab_breakdown_items');
        $this->addColumnsIfTableExists('rab_breakdown_budget_sources');

        // Backward compatibility (legacy table names before rename migration).
        $this->addColumnsIfTableExists('wbs_items');
        $this->addColumnsIfTableExists('wbs_budget_sources');
    }

    public function down(): void
    {
        $this->dropColumnsIfTableExists('rab_breakdown_budget_sources');
        $this->dropColumnsIfTableExists('rab_breakdown_items');
        $this->dropColumnsIfTableExists('wbs_budget_sources');
        $this->dropColumnsIfTableExists('wbs_items');
    }

    private function addColumnsIfTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'm2_peng')) {
                $table->decimal('m2_peng', 18, 6)->nullable()->after('berat_2');
            }
            if (!Schema::hasColumn($tableName, 'qty')) {
                $table->decimal('qty', 18, 6)->nullable()->after('m2_peng');
            }
            if (!Schema::hasColumn($tableName, 'qty_beli')) {
                $table->decimal('qty_beli', 18, 6)->nullable()->after('qty');
            }
            if (!Schema::hasColumn($tableName, 'jumlah')) {
                $table->decimal('jumlah', 18, 2)->nullable()->after('qty_beli');
            }
        });
    }

    private function dropColumnsIfTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn($tableName, 'm2_peng') ? 'm2_peng' : null,
            Schema::hasColumn($tableName, 'qty') ? 'qty' : null,
            Schema::hasColumn($tableName, 'qty_beli') ? 'qty_beli' : null,
            Schema::hasColumn($tableName, 'jumlah') ? 'jumlah' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};

