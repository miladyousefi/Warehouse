<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ActivityLog;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductMergeDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_duplicates_sums_stock_balances_and_moves_references(): void
    {
        Permission::create(['name' => 'products.delete']);

        $user = User::factory()->create();
        $user->givePermissionTo('products.delete');

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

        $keep = Product::create([
            'name_tr' => 'Kola',
            'name_en' => 'Cola',
            'sku' => 'KOLA-KEEP',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10,
        ]);

        $dup = Product::create([
            'name_tr' => 'Kola',
            'name_en' => 'Cola',
            'sku' => 'KOLA-DUP',
            'unit_id' => $unit->id,
            'is_active' => true,
            'track_quantity' => true,
            'min_stock' => 0,
            'max_stock' => 100,
            'unit_price' => 10,
        ]);

        StockBalance::create([
            'warehouse_id' => $w1->id,
            'product_id' => $keep->id,
            'quantity' => 5,
            'reserved_quantity' => 1,
        ]);
        StockBalance::create([
            'warehouse_id' => $w1->id,
            'product_id' => $dup->id,
            'quantity' => 2,
            'reserved_quantity' => 0.5,
        ]);
        StockBalance::create([
            'warehouse_id' => $w2->id,
            'product_id' => $dup->id,
            'quantity' => 3,
            'reserved_quantity' => 0,
        ]);

        StockMovement::create([
            'warehouse_id' => $w1->id,
            'product_id' => $dup->id,
            'type' => 'in',
            'quantity' => 2,
            'movement_date' => now(),
        ]);

        $log = ActivityLog::create([
            'action' => 'product.update',
            'subject_type' => Product::class,
            'subject_id' => $dup->id,
            'product_id' => $dup->id,
            'new_values' => ['product_id' => $dup->id, 'rows' => [['product_id' => $dup->id]]],
        ]);

        $this->actingAs($user);

        $res = $this->postJson(route('warehouse.products.merge-duplicates'), [
            'keep_product_id' => $keep->id,
            'remove_product_ids' => [$dup->id],
        ]);

        $res->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $dup->id]);

        $this->assertSame(
            7.0,
            (float) StockBalance::where('product_id', $keep->id)
                ->where('warehouse_id', $w1->id)
                ->value('quantity'),
        );
        $this->assertSame(
            1.5,
            (float) StockBalance::where('product_id', $keep->id)
                ->where('warehouse_id', $w1->id)
                ->value('reserved_quantity'),
        );
        $this->assertSame(
            3.0,
            (float) StockBalance::where('product_id', $keep->id)
                ->where('warehouse_id', $w2->id)
                ->value('quantity'),
        );

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $keep->id,
        ]);

        $log->refresh();
        $this->assertSame($keep->id, (int) $log->subject_id);
        $this->assertSame($keep->id, (int) $log->product_id);
        $this->assertSame($keep->id, (int) ($log->new_values['product_id'] ?? 0));
        $this->assertSame($keep->id, (int) ($log->new_values['rows'][0]['product_id'] ?? 0));
    }
}
