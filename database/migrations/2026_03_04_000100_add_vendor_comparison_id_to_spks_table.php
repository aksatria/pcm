<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            if (!Schema::hasColumn('spks', 'vendor_comparison_id')) {
                $table->unsignedBigInteger('vendor_comparison_id')->nullable()->after('vendor_id');
                $table->index(['vendor_comparison_id']);
                $table->foreign('vendor_comparison_id')
                    ->references('id')
                    ->on('vendor_comparisons')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table) {
            if (Schema::hasColumn('spks', 'vendor_comparison_id')) {
                $table->dropForeign(['vendor_comparison_id']);
                $table->dropIndex(['vendor_comparison_id']);
                $table->dropColumn('vendor_comparison_id');
            }
        });
    }
};
