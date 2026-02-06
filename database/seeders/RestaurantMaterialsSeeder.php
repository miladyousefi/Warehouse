<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class RestaurantMaterialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create units
        $kg = Unit::firstOrCreate(
            ['symbol' => 'kg'],
            ['name_tr' => 'Kilogram', 'name_en' => 'Kilogram', 'is_active' => true]
        );
        $liter = Unit::firstOrCreate(
            ['symbol' => 'L'],
            ['name_tr' => 'Liter', 'name_en' => 'Liter', 'is_active' => true]
        );
        $piece = Unit::firstOrCreate(
            ['symbol' => 'pcs'],
            ['name_tr' => 'Piece', 'name_en' => 'Piece', 'is_active' => true]
        );
        $gram = Unit::firstOrCreate(
            ['symbol' => 'g'],
            ['name_tr' => 'Gram', 'name_en' => 'Gram', 'is_active' => true]
        );
        $box = Unit::firstOrCreate(
            ['symbol' => 'box'],
            ['name_tr' => 'Box', 'name_en' => 'Box', 'is_active' => true]
        );

        // Create categories
        $meatCategory = ProductCategory::firstOrCreate(
            ['slug' => 'meat-protein'],
            [
                'name_tr' => 'Meat & Protein',
                'name_en' => 'Meat & Protein',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $vegetableCategory = ProductCategory::firstOrCreate(
            ['slug' => 'vegetables'],
            [
                'name_tr' => 'Vegetables',
                'name_en' => 'Vegetables',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $dairyCategory = ProductCategory::firstOrCreate(
            ['slug' => 'dairy'],
            [
                'name_tr' => 'Dairy',
                'name_en' => 'Dairy',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        $oilsCategory = ProductCategory::firstOrCreate(
            ['slug' => 'oils-spices'],
            [
                'name_tr' => 'Oils & Spices',
                'name_en' => 'Oils & Spices',
                'is_active' => true,
                'sort_order' => 4,
            ]
        );

        $grainCategory = ProductCategory::firstOrCreate(
            ['slug' => 'grains-flour'],
            [
                'name_tr' => 'Grains & Flour',
                'name_en' => 'Grains & Flour',
                'is_active' => true,
                'sort_order' => 5,
            ]
        );

        // Create warehouses - NO CODE FIELD
        $mainWarehouse = Warehouse::firstOrCreate(
            ['name_en' => 'Main Warehouse'],
            [
                'name_tr' => 'Main Warehouse',
                'address' => 'Main Storage Area',
                'phone' => '+90-555-123-4567',
                'is_active' => true,
            ]
        );

        $coldWarehouse = Warehouse::firstOrCreate(
            ['name_en' => 'Cold Storage'],
            [
                'name_tr' => 'Cold Storage',
                'address' => 'Refrigerated Storage Area',
                'phone' => '+90-555-123-4568',
                'is_active' => true,
            ]
        );

        $kitchenWarehouse = Warehouse::firstOrCreate(
            ['name_en' => 'Kitchen Warehouse'],
            [
                'name_tr' => 'Kitchen Warehouse',
                'address' => 'Kitchen Storage Area',
                'phone' => '+90-555-123-4569',
                'is_active' => true,
            ]
        );

        // Create suppliers
        $supplier1 = Supplier::firstOrCreate(
            ['email' => 'info@agricoops.com'],
            [
                'name' => 'Agricultural Cooperative',
                'contact_person' => 'John Smith',
                'phone' => '+90-555-200-1111',
                'address' => 'Agricultural District',
                'notes' => 'Fresh produce supplier',
                'is_active' => true,
            ]
        );

        $supplier2 = Supplier::firstOrCreate(
            ['email' => 'sales@imports.com'],
            [
                'name' => 'Food Imports Inc',
                'contact_person' => 'Sarah Johnson',
                'phone' => '+90-555-200-2222',
                'address' => 'Import Zone',
                'notes' => '15 days credit terms',
                'is_active' => true,
            ]
        );

        $supplier3 = Supplier::firstOrCreate(
            ['email' => 'meat@livestock.com'],
            [
                'name' => 'Livestock & Meat Co',
                'contact_person' => 'Robert Brown',
                'phone' => '+90-555-200-3333',
                'address' => 'Meat Processing Area',
                'notes' => 'Premium meat supplier',
                'is_active' => true,
            ]
        );

        $supplier4 = Supplier::firstOrCreate(
            ['email' => 'supply@dairypro.com'],
            [
                'name' => 'Dairy Products Pro',
                'contact_person' => 'Maria Garcia',
                'phone' => '+90-555-200-4444',
                'address' => 'Dairy Distribution',
                'notes' => '7 days credit terms',
                'is_active' => true,
            ]
        );

        // Create products - Meat & Protein
        $chickenBreast = Product::firstOrCreate(
            ['sku' => 'MEAT-001'],
            [
                'category_id' => $meatCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Chicken Breast',
                'name_en' => 'Chicken Breast',
                'barcode' => '1234567890001',
                'unit_price' => 8.50,
                'min_stock' => 20,
                'max_stock' => 100,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $beefRib = Product::firstOrCreate(
            ['sku' => 'MEAT-002'],
            [
                'category_id' => $meatCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Beef Rib',
                'name_en' => 'Beef Rib',
                'barcode' => '1234567890002',
                'unit_price' => 12.75,
                'min_stock' => 10,
                'max_stock' => 50,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $fish = Product::firstOrCreate(
            ['sku' => 'MEAT-003'],
            [
                'category_id' => $meatCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Fish Fillet',
                'name_en' => 'Fish Fillet',
                'barcode' => '1234567890003',
                'unit_price' => 10.50,
                'min_stock' => 15,
                'max_stock' => 60,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        // Create products - Vegetables
        $tomato = Product::firstOrCreate(
            ['sku' => 'VEG-001'],
            [
                'category_id' => $vegetableCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Tomato',
                'name_en' => 'Tomato',
                'barcode' => '1234567890004',
                'unit_price' => 1.50,
                'min_stock' => 30,
                'max_stock' => 150,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $onion = Product::firstOrCreate(
            ['sku' => 'VEG-002'],
            [
                'category_id' => $vegetableCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Onion',
                'name_en' => 'Onion',
                'barcode' => '1234567890005',
                'unit_price' => 0.80,
                'min_stock' => 25,
                'max_stock' => 120,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $garlic = Product::firstOrCreate(
            ['sku' => 'VEG-003'],
            [
                'category_id' => $vegetableCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Garlic',
                'name_en' => 'Garlic',
                'barcode' => '1234567890006',
                'unit_price' => 3.50,
                'min_stock' => 10,
                'max_stock' => 40,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $pepper = Product::firstOrCreate(
            ['sku' => 'VEG-004'],
            [
                'category_id' => $vegetableCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Green Pepper',
                'name_en' => 'Green Pepper',
                'barcode' => '1234567890007',
                'unit_price' => 2.25,
                'min_stock' => 15,
                'max_stock' => 70,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        // Create products - Dairy
        $yogurt = Product::firstOrCreate(
            ['sku' => 'DAIRY-001'],
            [
                'category_id' => $dairyCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Yogurt',
                'name_en' => 'Yogurt',
                'barcode' => '1234567890008',
                'unit_price' => 2.00,
                'min_stock' => 20,
                'max_stock' => 80,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $cheese = Product::firstOrCreate(
            ['sku' => 'DAIRY-002'],
            [
                'category_id' => $dairyCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Cheese',
                'name_en' => 'Cheese',
                'barcode' => '1234567890009',
                'unit_price' => 6.50,
                'min_stock' => 10,
                'max_stock' => 50,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $milk = Product::firstOrCreate(
            ['sku' => 'DAIRY-003'],
            [
                'category_id' => $dairyCategory->id,
                'unit_id' => $liter->id,
                'name_tr' => 'Milk',
                'name_en' => 'Milk',
                'barcode' => '1234567890010',
                'unit_price' => 1.25,
                'min_stock' => 30,
                'max_stock' => 150,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        // Create products - Oils & Spices
        $oliveoil = Product::firstOrCreate(
            ['sku' => 'OIL-001'],
            [
                'category_id' => $oilsCategory->id,
                'unit_id' => $liter->id,
                'name_tr' => 'Olive Oil',
                'name_en' => 'Olive Oil',
                'barcode' => '1234567890011',
                'unit_price' => 8.00,
                'min_stock' => 5,
                'max_stock' => 20,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $salt = Product::firstOrCreate(
            ['sku' => 'SPICE-001'],
            [
                'category_id' => $oilsCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Salt',
                'name_en' => 'Salt',
                'barcode' => '1234567890012',
                'unit_price' => 0.50,
                'min_stock' => 5,
                'max_stock' => 25,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        // Create products - Grains
        $rice = Product::firstOrCreate(
            ['sku' => 'GRAIN-001'],
            [
                'category_id' => $grainCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Rice',
                'name_en' => 'Rice',
                'barcode' => '1234567890013',
                'unit_price' => 2.50,
                'min_stock' => 20,
                'max_stock' => 100,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        $flour = Product::firstOrCreate(
            ['sku' => 'GRAIN-002'],
            [
                'category_id' => $grainCategory->id,
                'unit_id' => $kg->id,
                'name_tr' => 'Flour',
                'name_en' => 'Flour',
                'barcode' => '1234567890014',
                'unit_price' => 1.80,
                'min_stock' => 15,
                'max_stock' => 80,
                'is_active' => true,
                'track_quantity' => true,
            ]
        );

        // Create initial stock balances
        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $chickenBreast->id,
            ],
            [
                'quantity' => 50,
                'reserved_quantity' => 0,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $coldWarehouse->id,
                'product_id' => $chickenBreast->id,
            ],
            [
                'quantity' => 40,
                'reserved_quantity' => 10,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $tomato->id,
            ],
            [
                'quantity' => 80,
                'reserved_quantity' => 20,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $onion->id,
            ],
            [
                'quantity' => 60,
                'reserved_quantity' => 15,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $coldWarehouse->id,
                'product_id' => $yogurt->id,
            ],
            [
                'quantity' => 35,
                'reserved_quantity' => 5,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $coldWarehouse->id,
                'product_id' => $milk->id,
            ],
            [
                'quantity' => 70,
                'reserved_quantity' => 10,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $rice->id,
            ],
            [
                'quantity' => 60,
                'reserved_quantity' => 20,
            ]
        );

        StockBalance::firstOrCreate(
            [
                'warehouse_id' => $mainWarehouse->id,
                'product_id' => $flour->id,
            ],
            [
                'quantity' => 45,
                'reserved_quantity' => 10,
            ]
        );
    }
}

