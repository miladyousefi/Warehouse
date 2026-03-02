<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_menu_categories')) {
            return;
        }

        Schema::table('restaurant_menu_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_menu_categories', 'icon')) {
                $table->string('icon', 30)->nullable()->after('name_en');
            }

            if (!Schema::hasColumn('restaurant_menu_categories', 'image_path')) {
                $table->string('image_path')->nullable()->after('icon');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_menu_categories')) {
            return;
        }

        Schema::table('restaurant_menu_categories', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_menu_categories', 'image_path')) {
                $table->dropColumn('image_path');
            }

            if (Schema::hasColumn('restaurant_menu_categories', 'icon')) {
                $table->dropColumn('icon');
            }
        });
    }
};
