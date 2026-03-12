<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductStockBalancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_update_can_sync_stock_balances_per_warehouse(): void
    {
        Permission::create(['name' => 'products.edit']);

        $user = User::factory()->create();
        $user->givePermissionTo('products.edit');

        $unit = Unit::create([
            'name_tr' => 'Adet',
            'name_en' => 'Piece',
            'symbol' => 'pcs',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $w1 = Warehouse::create([
            'name_tr' => 'Depo 1',
            'name_en' => 'Warehouse 1',
            'code' => 'W1',
            'is_active' => true,
        ]);
        $w2 = Warehouse::create([
            'name_tr' => 'Depo 2',
            'name_en' => 'Warehouse 2',
            'code' => 'W2',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name_tr' => 'Test',
            'name_en' => 'Test',
            'sku' => 'TEST-001',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10,
        ]);

        StockBalance::create([
            'warehouse_id' => $w1->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user);

        $response = $this->put(route('warehouse.products.update', ['product' => $product->id]), [
            'unit_id' => $unit->id,
            'name_tr' => 'Test',
            'name_en' => 'Test',
            'stock_balances' => [
                ['warehouse_id' => $w1->id, 'quantity' => 10],
                ['warehouse_id' => $w2->id, 'quantity' => 3.5],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $w1->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $w2->id,
            'product_id' => $product->id,
        ]);

        $this->assertSame(
            10.0,
            (float) StockBalance::where('warehouse_id', $w1->id)->where('product_id', $product->id)->value('quantity')
        );
        $this->assertSame(
            3.5,
            (float) StockBalance::where('warehouse_id', $w2->id)->where('product_id', $product->id)->value('quantity')
        );
    }
}
