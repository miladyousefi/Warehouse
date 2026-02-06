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
