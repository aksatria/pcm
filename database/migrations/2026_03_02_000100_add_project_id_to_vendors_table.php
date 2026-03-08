<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('id');
                $table->index('project_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'project_id')) {
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};
