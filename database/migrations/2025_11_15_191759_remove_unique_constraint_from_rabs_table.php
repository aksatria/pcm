<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rabs', function (Blueprint $table) {
            // Hapus unique constraint
            $table->dropUnique(['project_id', 'version']);
        });
    }

    public function down()
    {
        Schema::table('rabs', function (Blueprint $table) {
            $table->unique(['project_id', 'version']);
        });
    }
};