<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Task;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Notification;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WarehouseFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create necessary permissions
        Permission::create(['name' => 'stock.adjustment']);
        Permission::create(['name' => 'stock.transfer']);
        Permission::create(['name' => 'task.create']);
        Permission::create(['name' => 'task.view']);
        Permission::create(['name' => 'task.edit']);
        Permission::create(['name' => 'task.assign']);
        Permission::create(['name' => 'task.delete']);
    }

    public function test_negative_stock_adjustment()
    {
        // Setup
        $user = User::factory()->create();
        $user->givePermissionTo('stock.adjustment');

        $unit = Unit::create([
            'name_tr' => 'Adet',
            'name_en' => 'Piece',
            'symbol' => 'pcs',
            'is_active' => true,
            'sort_order' => 1
        ]);
        $warehouse = Warehouse::create([
            'name_tr' => 'Main',
            'name_en' => 'Main',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name_tr' => 'Test',
            'name_en' => 'Test',
            'sku' => 'TEST001',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'is_active' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10
        ]);

        // Initial balance
        StockBalance::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100
        ]);

        $this->actingAs($user);

        // Action: Submit negative adjustment
        $response = $this->post(route('warehouse.stock-movements.store'), [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => -5, // Negative quantity
            'movement_date' => now()->format('Y-m-d'),
        ]);

        // Assert
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => -5,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 95, // 100 + (-5)
        ]);
    }

    public function test_task_notification_on_assignment()
    {
        $user = User::factory()->create();
        $permissions = ['task.view', 'task.create', 'task.edit', 'task.assign'];
        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        $assignee = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('warehouse.tasks.store'), [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'priority' => 'medium',
            'assigned_to' => $assignee->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'assigned_to' => $assignee->id,
        ]);

        // Assert Notification created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $assignee->id,
            'type' => 'task_assigned',
            'title' => 'New Task Assigned',
        ]);
    }

    public function test_transfer_to_existing_product_in_destination_warehouse_adds_quantity(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('stock.transfer');

        $unit = Unit::create([
            'name_tr' => 'Adet',
            'name_en' => 'Piece',
            'symbol' => 'pcs',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $fromWarehouse = Warehouse::create([
            'name_tr' => 'From',
            'name_en' => 'From',
            'code' => 'FROM',
            'is_active' => true,
        ]);

        $toWarehouse = Warehouse::create([
            'name_tr' => 'To',
            'name_en' => 'To',
            'code' => 'TO',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name_tr' => 'Product Name1',
            'name_en' => 'Product Name1',
            'sku' => 'PRD-TR-1',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10,
            'warehouse_id' => $fromWarehouse->id,
        ]);

        StockBalance::create([
            'warehouse_id' => $fromWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        StockBalance::create([
            'warehouse_id' => $toWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('warehouse.stock-movements.store'), [
            'type' => 'transfer',
            'from_warehouse_id' => $fromWarehouse->id,
            'rows' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $toWarehouse->id,
                    // quantity intentionally omitted
                ],
            ],
            'movement_date' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('warehouse.stock-movements.index'));

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'transfer',
            'product_id' => $product->id,
            'from_warehouse_id' => $fromWarehouse->id,
            'warehouse_id' => $toWarehouse->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $fromWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $toWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
        ]);
    }

    public function test_products_excel_export_respects_warehouse_filter(): void
    {
        Excel::fake();
        Excel::matchByRegex();

        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'products.view']);
        $user->givePermissionTo('products.view');

        $unit = Unit::create([
            'name_tr' => 'Adet',
            'name_en' => 'Piece',
            'symbol' => 'pcs',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $warehouse1 = Warehouse::create([
            'name_tr' => 'W1',
            'name_en' => 'W1',
            'code' => 'W1',
            'is_active' => true,
        ]);

        $warehouse2 = Warehouse::create([
            'name_tr' => 'W2',
            'name_en' => 'W2',
            'code' => 'W2',
            'is_active' => true,
        ]);

        $productInW1 = Product::create([
            'name_tr' => 'P1',
            'name_en' => 'P1',
            'sku' => 'P1',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $productInW2Only = Product::create([
            'name_tr' => 'P2',
            'name_en' => 'P2',
            'sku' => 'P2',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        StockBalance::create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $productInW1->id,
            'quantity' => 3,
        ]);

        StockBalance::create([
            'warehouse_id' => $warehouse2->id,
            'product_id' => $productInW2Only->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('warehouse.products.export-excel', [
            'warehouse_id' => $warehouse1->id,
        ]));

        $response->assertOk();

        Excel::assertDownloaded('/products-.*\\.xlsx/', function ($export) use ($productInW1, $productInW2Only) {
            $collection = method_exists($export, 'collection') ? $export->collection() : collect();
            $ids = $collection->pluck('id')->all();

            return in_array($productInW1->id, $ids, true) && !in_array($productInW2Only->id, $ids, true);
        });
    }

    public function test_products_excel_export_selected_single_product_is_not_empty(): void
    {
        Excel::fake();
        Excel::matchByRegex();

        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'products.view']);
        $user->givePermissionTo('products.view');

        $unit = Unit::create([
            'name_tr' => 'Adet',
            'name_en' => 'Piece',
            'symbol' => 'pcs',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name_tr' => 'Selected',
            'name_en' => 'Selected',
            'sku' => 'SEL1',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('warehouse.products.export-excel'), [
            'product_ids' => [$product->id],
        ]);

        $response->assertOk();

        Excel::assertDownloaded('/products-.*\\.xlsx/', function ($export) use ($product) {
            $collection = method_exists($export, 'collection') ? $export->collection() : collect();
            return $collection->pluck('id')->contains($product->id);
        });
    }

    public function test_translations_keys_exist()
    {
        $en = json_decode(file_get_contents(base_path('lang/en.json')), true);
        $tr = json_decode(file_get_contents(base_path('lang/tr.json')), true);

        $this->assertArrayHasKey('validation.required', $en);
        $this->assertArrayHasKey('validation.unique', $en);
        $this->assertArrayHasKey('accounting.income', $tr);
    }

    public function test_task_list_access()
    {
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'task.view']);
        $user->givePermissionTo($permission);

        $this->actingAs($user);

        $response = $this->get(route('warehouse.tasks.index'));

        $response->assertOk();
    }

    public function test_accounting_entry_creation()
    {
        $user = User::factory()->create();
        $permissions = ['accounting.create', 'accounting.view'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p]);
            $user->givePermissionTo($p);
        }

        $this->actingAs($user);

        $response = $this->post(route('warehouse.accounting.store'), [
            'date' => now()->format('Y-m-d'),
            'type' => 'income',
            'category' => 'sales', // Key from controller
            'amount' => 100,
            'description' => 'Test Income',
            'notes' => 'Test Notes',
        ]);

        $response->assertRedirect(route('warehouse.accounting.index'));
        $this->assertDatabaseHas('accounting_entries', [
            'type' => 'income',
            'category' => 'sales',
            'amount' => 100,
            'created_by' => $user->id,
        ]);
    }
}
