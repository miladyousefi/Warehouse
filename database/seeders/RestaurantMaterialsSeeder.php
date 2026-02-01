<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
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
        $kg = Unit::create(['name' => 'کیلوگرم', 'abbreviation' => 'kg']);
        $liter = Unit::create(['name' => 'لیتر', 'abbreviation' => 'L']);
        $unit = Unit::create(['name' => 'عدد', 'abbreviation' => 'pcs']);
        $gram = Unit::create(['name' => 'گرم', 'abbreviation' => 'g']);
        $box = Unit::create(['name' => 'جعبه', 'abbreviation' => 'box']);

        // Create categories
        $meatCategory = ProductCategory::create([
            'name_tr' => 'گوشت و پروتئین',
            'name_en' => 'Meat & Protein',
            'slug' => 'meat-protein',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $vegetableCategory = ProductCategory::create([
            'name_tr' => 'سبزیجات',
            'name_en' => 'Vegetables',
            'slug' => 'vegetables',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $fruitCategory = ProductCategory::create([
            'name_tr' => 'میوه‌ها',
            'name_en' => 'Fruits',
            'slug' => 'fruits',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $dairyCategory = ProductCategory::create([
            'name_tr' => 'لبنیات',
            'name_en' => 'Dairy',
            'slug' => 'dairy',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $oilsCategory = ProductCategory::create([
            'name_tr' => 'روغن و ادویه',
            'name_en' => 'Oils & Spices',
            'slug' => 'oils-spices',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $grainCategory = ProductCategory::create([
            'name_tr' => 'غلات و آرد',
            'name_en' => 'Grains & Flour',
            'slug' => 'grains-flour',
            'is_active' => true,
            'sort_order' => 6,
        ]);

        $seasonsCategory = ProductCategory::create([
            'name_tr' => 'فصل‌ها و ادویه‌جات',
            'name_en' => 'Seasonings & Herbs',
            'slug' => 'seasonings',
            'is_active' => true,
            'sort_order' => 7,
        ]);

        // Create warehouses
        $mainWarehouse = Warehouse::create([
            'name' => 'انبار اصلی',
            'address' => 'تهران، خیابان ولیعصر',
            'phone' => '021-12345678',
            'manager' => 'احمد محمدی',
        ]);

        $coldWarehouse = Warehouse::create([
            'name' => 'انبار یخچال‌دار',
            'address' => 'تهران، خیابان شریاتی',
            'phone' => '021-87654321',
            'manager' => 'فاطمه علوی',
        ]);

        $kitchenWarehouse = Warehouse::create([
            'name' => 'انبار آشپزخانه',
            'address' => 'تهران، میدان فردوسی',
            'phone' => '021-55555555',
            'manager' => 'علی رضایی',
        ]);

        // Create suppliers
        $supplier1 = Supplier::create([
            'name' => 'شرکت تعاونی کشاورزی تهران',
            'contact_person' => 'محمد حسین',
            'email' => 'info@tehran-coop.ir',
            'phone' => '021-11111111',
            'address' => 'تهران، بازار تره‌بار',
            'payment_terms' => 'نقد',
            'min_order' => 50,
        ]);

        $supplier2 = Supplier::create([
            'name' => 'واردات مواد غذایی ایرانیان',
            'contact_person' => 'زهرا کریمی',
            'email' => 'sales@iraniimports.ir',
            'phone' => '021-22222222',
            'address' => 'تهران، منطقه صادرات',
            'payment_terms' => '15 روز اعتباری',
            'min_order' => 100,
        ]);

        $supplier3 = Supplier::create([
            'name' => 'انبار گوشت و دام پاک',
            'contact_person' => 'رضا سلیمی',
            'email' => 'meat@pak-livestock.ir',
            'phone' => '021-33333333',
            'address' => 'تهران، کمرج',
            'payment_terms' => 'نقد',
            'min_order' => 20,
        ]);

        $supplier4 = Supplier::create([
            'name' => 'شرکت لبنی پاستور',
            'contact_person' => 'مریم خدایی',
            'email' => 'supply@pastor-dairy.ir',
            'phone' => '021-44444444',
            'address' => 'تهران، منطقه دستگردی',
            'payment_terms' => '7 روز اعتباری',
            'min_order' => 30,
        ]);

        // Create products - Meat & Protein
        $chickenBreast = Product::create([
            'category_id' => $meatCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'سینه مرغ',
            'name_en' => 'Chicken Breast',
            'sku' => 'MEAT-001',
            'barcode' => '1234567890001',
            'min_stock' => 20,
            'max_stock' => 100,
            'cost_price' => 25000,
            'selling_price' => 35000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $beefRib = Product::create([
            'category_id' => $meatCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'دنده گاو',
            'name_en' => 'Beef Rib',
            'sku' => 'MEAT-002',
            'barcode' => '1234567890002',
            'min_stock' => 10,
            'max_stock' => 50,
            'cost_price' => 45000,
            'selling_price' => 65000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $fish = Product::create([
            'category_id' => $meatCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'ماهی شیر',
            'name_en' => 'Fish Fillet',
            'sku' => 'MEAT-003',
            'barcode' => '1234567890003',
            'min_stock' => 15,
            'max_stock' => 60,
            'cost_price' => 35000,
            'selling_price' => 55000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        // Create products - Vegetables
        $tomato = Product::create([
            'category_id' => $vegetableCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'گوجه‌فرنگی',
            'name_en' => 'Tomato',
            'sku' => 'VEG-001',
            'barcode' => '1234567890004',
            'min_stock' => 30,
            'max_stock' => 150,
            'cost_price' => 8000,
            'selling_price' => 12000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $onion = Product::create([
            'category_id' => $vegetableCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'پیاز',
            'name_en' => 'Onion',
            'sku' => 'VEG-002',
            'barcode' => '1234567890005',
            'min_stock' => 25,
            'max_stock' => 120,
            'cost_price' => 5000,
            'selling_price' => 8000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $garlic = Product::create([
            'category_id' => $vegetableCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'سیر',
            'name_en' => 'Garlic',
            'sku' => 'VEG-003',
            'barcode' => '1234567890006',
            'min_stock' => 10,
            'max_stock' => 40,
            'cost_price' => 12000,
            'selling_price' => 18000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $pepper = Product::create([
            'category_id' => $vegetableCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'فلفل سبز',
            'name_en' => 'Green Pepper',
            'sku' => 'VEG-004',
            'barcode' => '1234567890007',
            'min_stock' => 15,
            'max_stock' => 70,
            'cost_price' => 10000,
            'selling_price' => 15000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        // Create products - Dairy
        $yogurt = Product::create([
            'category_id' => $dairyCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'ماست',
            'name_en' => 'Yogurt',
            'sku' => 'DAIRY-001',
            'barcode' => '1234567890008',
            'min_stock' => 20,
            'max_stock' => 80,
            'cost_price' => 15000,
            'selling_price' => 22000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $cheese = Product::create([
            'category_id' => $dairyCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'پنیر',
            'name_en' => 'Cheese',
            'sku' => 'DAIRY-002',
            'barcode' => '1234567890009',
            'min_stock' => 10,
            'max_stock' => 50,
            'cost_price' => 40000,
            'selling_price' => 60000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $milk = Product::create([
            'category_id' => $dairyCategory->id,
            'unit_id' => $liter->id,
            'name_tr' => 'شیر',
            'name_en' => 'Milk',
            'sku' => 'DAIRY-003',
            'barcode' => '1234567890010',
            'min_stock' => 30,
            'max_stock' => 150,
            'cost_price' => 12000,
            'selling_price' => 16000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        // Create products - Oils & Spices
        $oliveoil = Product::create([
            'category_id' => $oilsCategory->id,
            'unit_id' => $liter->id,
            'name_tr' => 'روغن زیتون',
            'name_en' => 'Olive Oil',
            'sku' => 'OIL-001',
            'barcode' => '1234567890011',
            'min_stock' => 5,
            'max_stock' => 20,
            'cost_price' => 120000,
            'selling_price' => 180000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $salt = Product::create([
            'category_id' => $oilsCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'نمک',
            'name_en' => 'Salt',
            'sku' => 'SPICE-001',
            'barcode' => '1234567890012',
            'min_stock' => 5,
            'max_stock' => 25,
            'cost_price' => 3000,
            'selling_price' => 5000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        // Create products - Grains
        $rice = Product::create([
            'category_id' => $grainCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'برنج',
            'name_en' => 'Rice',
            'sku' => 'GRAIN-001',
            'barcode' => '1234567890013',
            'min_stock' => 20,
            'max_stock' => 100,
            'cost_price' => 18000,
            'selling_price' => 25000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $flour = Product::create([
            'category_id' => $grainCategory->id,
            'unit_id' => $kg->id,
            'name_tr' => 'آرد',
            'name_en' => 'Flour',
            'sku' => 'GRAIN-002',
            'barcode' => '1234567890014',
            'min_stock' => 15,
            'max_stock' => 80,
            'cost_price' => 8000,
            'selling_price' => 12000,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        // Create initial stock balances
        StockBalance::create([
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $chickenBreast->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        StockBalance::create([
            'warehouse_id' => $coldWarehouse->id,
            'product_id' => $chickenBreast->id,
            'quantity' => 40,
            'reserved_quantity' => 10,
        ]);

        StockBalance::create([
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $tomato->id,
            'quantity' => 80,
            'reserved_quantity' => 20,
        ]);

        StockBalance::create([
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $onion->id,
            'quantity' => 60,
            'reserved_quantity' => 15,
        ]);

        StockBalance::create([
            'warehouse_id' => $coldWarehouse->id,
            'product_id' => $yogurt->id,
            'quantity' => 35,
            'reserved_quantity' => 5,
        ]);

        StockBalance::create([
            'warehouse_id' => $coldWarehouse->id,
            'product_id' => $milk->id,
            'quantity' => 70,
            'reserved_quantity' => 10,
        ]);

        StockBalance::create([
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $rice->id,
            'quantity' => 60,
            'reserved_quantity' => 20,
        ]);

        StockBalance::create([
            'warehouse_id' => $mainWarehouse->id,
            'product_id' => $flour->id,
            'quantity' => 45,
            'reserved_quantity' => 10,
        ]);

        // Create purchase orders
        $po1 = PurchaseOrder::create([
            'supplier_id' => $supplier1->id,
            'warehouse_id' => $mainWarehouse->id,
            'order_number' => 'PO-2026-001',
            'status' => 'received',
            'order_date' => now()->subDays(15),
            'expected_date' => now()->subDays(10),
            'received_date' => now()->subDays(8),
            'subtotal' => 2400000,
            'tax_amount' => 240000,
            'total' => 2640000,
            'notes' => 'تحویل کامل',
            'created_by' => 1,
            'approved_by' => 1,
            'approved_at' => now()->subDays(14),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'product_id' => $tomato->id,
            'quantity' => 100,
            'unit_price' => 8000,
            'total_price' => 800000,
            'received_quantity' => 100,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'product_id' => $onion->id,
            'quantity' => 80,
            'unit_price' => 5000,
            'total_price' => 400000,
            'received_quantity' => 80,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po1->id,
            'product_id' => $garlic->id,
            'quantity' => 20,
            'unit_price' => 12000,
            'total_price' => 240000,
            'received_quantity' => 20,
        ]);

        // Purchase order for meat
        $po2 = PurchaseOrder::create([
            'supplier_id' => $supplier3->id,
            'warehouse_id' => $coldWarehouse->id,
            'order_number' => 'PO-2026-002',
            'status' => 'received',
            'order_date' => now()->subDays(7),
            'expected_date' => now()->subDays(5),
            'received_date' => now()->subDays(3),
            'subtotal' => 1050000,
            'tax_amount' => 105000,
            'total' => 1155000,
            'notes' => 'تحویل کامل و با کیفیت خوب',
            'created_by' => 1,
            'approved_by' => 1,
            'approved_at' => now()->subDays(6),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'product_id' => $chickenBreast->id,
            'quantity' => 30,
            'unit_price' => 25000,
            'total_price' => 750000,
            'received_quantity' => 30,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'product_id' => $beefRib->id,
            'quantity' => 5,
            'unit_price' => 45000,
            'total_price' => 225000,
            'received_quantity' => 5,
        ]);

        // Purchase order for dairy
        $po3 = PurchaseOrder::create([
            'supplier_id' => $supplier4->id,
            'warehouse_id' => $coldWarehouse->id,
            'order_number' => 'PO-2026-003',
            'status' => 'partial',
            'order_date' => now()->subDays(5),
            'expected_date' => now()->addDays(2),
            'subtotal' => 1350000,
            'tax_amount' => 135000,
            'total' => 1485000,
            'notes' => 'سفارش تحت پردازش',
            'created_by' => 1,
            'approved_by' => 1,
            'approved_at' => now()->subDays(4),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po3->id,
            'product_id' => $yogurt->id,
            'quantity' => 50,
            'unit_price' => 15000,
            'total_price' => 750000,
            'received_quantity' => 35,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po3->id,
            'product_id' => $milk->id,
            'quantity' => 40,
            'unit_price' => 12000,
            'total_price' => 480000,
            'received_quantity' => 40,
        ]);
    }
}
