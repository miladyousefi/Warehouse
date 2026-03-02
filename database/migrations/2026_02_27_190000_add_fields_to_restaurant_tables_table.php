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
            if (!Schema::hasColumn('restaurant_tables', 'table_number')) {
                $table->string('table_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('restaurant_tables', 'name')) {
                $table->string('name')->nullable()->after('table_number');
            }
            if (!Schema::hasColumn('restaurant_tables', 'capacity')) {
                $table->unsignedInteger('capacity')->default(1)->after('name');
            }
            if (!Schema::hasColumn('restaurant_tables', 'section')) {
                $table->string('section')->nullable()->after('capacity');
            }
            if (!Schema::hasColumn('restaurant_tables', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('section');
            }
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            // Add unique index only if not already present.
            try {
                $table->unique('table_number', 'restaurant_tables_table_number_unique');
            } catch (Throwable $e) {
                // Ignore duplicate/exists errors for cross-environment compatibility.
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_tables')) {
            return;
        }

        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_tables', 'table_number')) {
                try {
                    $table->dropUnique('restaurant_tables_table_number_unique');
                } catch (Throwable $e) {
                    // Ignore if index does not exist.
                }
            }
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_tables', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('restaurant_tables', 'section')) {
                $table->dropColumn('section');
            }
            if (Schema::hasColumn('restaurant_tables', 'capacity')) {
                $table->dropColumn('capacity');
            }
            if (Schema::hasColumn('restaurant_tables', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('restaurant_tables', 'table_number')) {
                $table->dropColumn('table_number');
            }
        });
    }
};
