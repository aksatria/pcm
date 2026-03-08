<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_settings', 'email_enabled')) {
                $table->boolean('email_enabled')->default(true)->after('muted_types');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('notification_settings', 'email_enabled')) {
                $table->dropColumn('email_enabled');
            }
        });
    }
};
