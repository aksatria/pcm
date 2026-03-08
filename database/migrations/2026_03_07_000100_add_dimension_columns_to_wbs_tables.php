<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wbs_items', function (Blueprint $table) {
            $table->decimal('p', 15, 4)->nullable()->after('total_price');
            $table->decimal('l', 15, 4)->nullable()->after('p');
            $table->decimal('t', 15, 4)->nullable()->after('l');
            $table->decimal('n', 15, 4)->nullable()->after('t');
            $table->decimal('n_tul_1', 15, 4)->nullable()->after('n');
            $table->decimal('n_tul_2', 15, 4)->nullable()->after('n_tul_1');
            $table->decimal('jarak', 15, 4)->nullable()->after('n_tul_2');
            $table->decimal('dia_1', 15, 4)->nullable()->after('jarak');
        });

        Schema::table('wbs_budget_sources', function (Blueprint $table) {
            $table->decimal('p', 15, 4)->nullable()->after('allocated_amount');
            $table->decimal('l', 15, 4)->nullable()->after('p');
            $table->decimal('t', 15, 4)->nullable()->after('l');
            $table->decimal('n', 15, 4)->nullable()->after('t');
            $table->decimal('n_tul_1', 15, 4)->nullable()->after('n');
            $table->decimal('n_tul_2', 15, 4)->nullable()->after('n_tul_1');
            $table->decimal('jarak', 15, 4)->nullable()->after('n_tul_2');
            $table->decimal('dia_1', 15, 4)->nullable()->after('jarak');
        });
    }

    public function down(): void
    {
        Schema::table('wbs_budget_sources', function (Blueprint $table) {
            $table->dropColumn(['p', 'l', 't', 'n', 'n_tul_1', 'n_tul_2', 'jarak', 'dia_1']);
        });

        Schema::table('wbs_items', function (Blueprint $table) {
            $table->dropColumn(['p', 'l', 't', 'n', 'n_tul_1', 'n_tul_2', 'jarak', 'dia_1']);
        });
    }
};
