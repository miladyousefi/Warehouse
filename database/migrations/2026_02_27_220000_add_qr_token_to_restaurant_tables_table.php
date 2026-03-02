<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_tables')) {
            return;
        }

        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_tables', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->after('table_number');
                $table->unique('qr_token', 'restaurant_tables_qr_token_unique');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_tables')) {
            return;
        }

        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_tables', 'qr_token')) {
                $table->dropUnique('restaurant_tables_qr_token_unique');
                $table->dropColumn('qr_token');
            }
        });
    }
};
