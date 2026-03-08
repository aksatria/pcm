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

        // Backward compatibility for legacy naming before schema rename migration.
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
            if (!Schema::hasColumn($tableName, 'dia_2')) {
                $table->decimal('dia_2', 15, 4)->nullable()->after('dia_1');
            }
            if (!Schema::hasColumn($tableName, 'dia_3')) {
                $table->decimal('dia_3', 15, 4)->nullable()->after('dia_2');
            }
            if (!Schema::hasColumn($tableName, 'berat_1')) {
                $table->decimal('berat_1', 15, 4)->nullable()->after('dia_3');
            }
            if (!Schema::hasColumn($tableName, 'berat_2')) {
                $table->decimal('berat_2', 15, 4)->nullable()->after('berat_1');
            }
        });
    }

    private function dropColumnsIfTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn($tableName, 'dia_2') ? 'dia_2' : null,
            Schema::hasColumn($tableName, 'dia_3') ? 'dia_3' : null,
            Schema::hasColumn($tableName, 'berat_1') ? 'berat_1' : null,
            Schema::hasColumn($tableName, 'berat_2') ? 'berat_2' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};

