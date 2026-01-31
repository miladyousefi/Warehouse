<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WarehouseInitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name_tr' => 'Adet', 'name_en' => 'Piece', 'symbol' => 'adet'],
            ['name_tr' => 'Kilogram', 'name_en' => 'Kilogram', 'symbol' => 'kg'],
            ['name_tr' => 'Gram', 'name_en' => 'Gram', 'symbol' => 'g'],
            ['name_tr' => 'Litre', 'name_en' => 'Litre', 'symbol' => 'L'],
            ['name_tr' => 'Mililitre', 'name_en' => 'Millilitre', 'symbol' => 'ml'],
            ['name_tr' => 'Kutu', 'name_en' => 'Box', 'symbol' => 'kutu'],
            ['name_tr' => 'Paket', 'name_en' => 'Package', 'symbol' => 'paket'],
            ['name_tr' => 'Demet', 'name_en' => 'Bunch', 'symbol' => 'demet'],
        ];

        foreach ($units as $i => $u) {
            Unit::firstOrCreate(
                ['symbol' => $u['symbol']],
                array_merge($u, ['sort_order' => $i + 1])
            );
        }

        $categories = [
            ['name_tr' => 'Sebzeler', 'name_en' => 'Vegetables'],
            ['name_tr' => 'Meyveler', 'name_en' => 'Fruits'],
            ['name_tr' => 'Et & Tavuk', 'name_en' => 'Meat & Poultry'],
            ['name_tr' => 'Süt Ürünleri', 'name_en' => 'Dairy'],
            ['name_tr' => 'İçecekler', 'name_en' => 'Beverages'],
            ['name_tr' => 'Baharat & Soslar', 'name_en' => 'Spices & Sauces'],
            ['name_tr' => 'Kuru Gıda', 'name_en' => 'Dry Goods'],
        ];

        foreach ($categories as $i => $c) {
            $slug = Str::slug($c['name_en']);
            ProductCategory::firstOrCreate(
                ['slug' => $slug],
                array_merge($c, ['sort_order' => $i + 1])
            );
        }

        Warehouse::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name_tr' => 'Ana Depo',
                'name_en' => 'Main Warehouse',
                'address' => null,
                'sort_order' => 1,
            ]
        );

        Warehouse::firstOrCreate(
            ['code' => 'KITCHEN'],
            [
                'name_tr' => 'Mutfak Deposu',
                'name_en' => 'Kitchen Store',
                'address' => null,
                'sort_order' => 2,
            ]
        );
    }
}
