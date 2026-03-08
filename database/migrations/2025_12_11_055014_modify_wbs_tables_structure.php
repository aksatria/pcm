<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hapus constraint NOT NULL dari project_id
        Schema::table('work_breakdown_structures', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
        });
        
        // 2. Tambah field is_template untuk membedakan template vs project WBS
        Schema::table('work_breakdown_structures', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('work_breakdown_structures', function (Blueprint $table) {
            $table->dropColumn('is_template');
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};