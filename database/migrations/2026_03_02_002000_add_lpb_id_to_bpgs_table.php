<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpgs', function (Blueprint $table) {
            if (!Schema::hasColumn('bpgs', 'lpb_id')) {
                $table->unsignedBigInteger('lpb_id')->nullable()->after('rab_id');
                $table->index(['lpb_id']);
                $table->foreign('lpb_id')->references('id')->on('lpbs')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bpgs', function (Blueprint $table) {
            if (Schema::hasColumn('bpgs', 'lpb_id')) {
                $table->dropForeign(['lpb_id']);
                $table->dropIndex(['lpb_id']);
                $table->dropColumn('lpb_id');
            }
        });
    }
};
