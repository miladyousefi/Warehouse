<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_menu_settings', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->after('is_public');
            $table->string('cover_image_path')->nullable()->after('share_token');
            $table->string('background_image_path')->nullable()->after('cover_image_path');
            $table->index('share_token', 'rms_share_token_idx');
        });

        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description_en');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_menu_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('restaurant_menu_settings', function (Blueprint $table) {
            $table->dropIndex('rms_share_token_idx');
            $table->dropColumn(['share_token', 'cover_image_path', 'background_image_path']);
        });
    }
};
