<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryAndNotesToClientsTable extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'category')) {
                $table->string('category')->default('Corporate')->after('company');
            }
            
            if (!Schema::hasColumn('clients', 'notes')) {
                $table->text('notes')->nullable()->after('category');
            }
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['category', 'notes']);
        });
    }
}