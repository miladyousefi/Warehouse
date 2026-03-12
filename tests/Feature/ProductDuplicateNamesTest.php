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

class ProductDuplicateNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_names_endpoint_returns_groups_with_stock_balances(): void
    {
        Permission::create(['name' => 'products.view']);

        $user = User::factory()->create();
        $user->givePermissionTo('products.view');

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

        $p1 = Product::create([
            'name_tr' => 'Kola',
            'name_en' => 'Cola',
            'sku' => 'KOLA-1',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10,
        ]);

        $p2 = Product::create([
            'name_tr' => 'Kola',
            'name_en' => 'Cola 2',
            'sku' => 'KOLA-2',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10,
        ]);

        StockBalance::create([
            'warehouse_id' => $w1->id,
            'product_id' => $p1->id,
            'quantity' => 7,
        ]);

        $this->actingAs($user);

        $res = $this->get(route('warehouse.products.duplicate-names'));
        $res->assertOk();
        $res->assertJsonStructure([
            'name_tr',
            'name_en',
        ]);

        $payload = $res->json();
        $this->assertNotEmpty($payload['name_tr']);

        $group = collect($payload['name_tr'])->firstWhere('key', 'Kola');
        $this->assertNotNull($group);
        $this->assertCount(2, $group['products']);

        $productWithBalance = collect($group['products'])->firstWhere('id', $p1->id);
        $this->assertNotNull($productWithBalance);
        $this->assertNotEmpty($productWithBalance['stock_balances']);
        $this->assertSame($w1->id, $productWithBalance['stock_balances'][0]['warehouse_id']);
    }
}

