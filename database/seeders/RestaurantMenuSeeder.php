<?php

namespace Database\Seeders;

use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RestaurantMenuSeeder extends Seeder
{
    public function run(): void
    {
        $settingPayload = [
            'layout_type' => 'template_1',
            'is_public' => true,
        ];

        if (Schema::hasTable('restaurant_menu_settings') && Schema::hasColumn('restaurant_menu_settings', 'share_token')) {
            $settingPayload['share_token'] = Str::random(32);
        }

        RestaurantMenuSetting::query()->firstOrCreate([], $settingPayload);

        RestaurantMenuCategory::query()->firstOrCreate(
            ['name_en' => 'Main Dishes'],
            ['name_tr' => 'Ana Yemekler', 'is_active' => true, 'sort_order' => 1]
        );

        RestaurantMenuCategory::query()->firstOrCreate(
            ['name_en' => 'Drinks'],
            ['name_tr' => 'İçecekler', 'is_active' => true, 'sort_order' => 2]
        );
    }
}
