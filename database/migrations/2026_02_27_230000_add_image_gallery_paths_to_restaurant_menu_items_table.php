<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_menu_items')) {
            return;
        }

        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_menu_items', 'image_gallery_paths')) {
                $table->json('image_gallery_paths')->nullable()->after('image_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_menu_items')) {
            return;
        }

        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('restaurant_menu_items', 'image_gallery_paths')) {
                $table->dropColumn('image_gallery_paths');
            }
        });
    }
};
