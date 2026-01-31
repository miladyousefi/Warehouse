<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StorePurchaseOrderRequest;
use App\Http\Requests\Warehouse\UpdatePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('purchase_orders.view');

        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'createdBy'])
            ->when($request->search, fn ($q) => $q->where('order_number', 'like', "%{$request->search}%")
                ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$request->search}%")))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('order_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('warehouse/purchase-orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('purchase_orders.create');

        return Inertia::render('warehouse/purchase-orders/Create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name_tr')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $orderNumber = 'PO-' . now()->format('Ymd') . '-' . str_pad((string) (PurchaseOrder::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
        $order = PurchaseOrder::create([
            'supplier_id' => $validated['supplier_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'order_number' => $orderNumber,
            'status' => PurchaseOrder::STATUS_DRAFT,
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $total = (float) $item['quantity'] * (float) $item['unit_price'];
            $subtotal += $total;
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ]);
        }
        $order->update(['subtotal' => $subtotal, 'tax_amount' => 0, 'total' => $subtotal]);

        return redirect()->route('warehouse.purchase-orders.show', $order)->with('success', __('Purchase order created.'));
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('purchase_orders.view');

        $purchaseOrder->load(['supplier', 'warehouse', 'items.product.unit', 'createdBy']);

        return Inertia::render('warehouse/purchase-orders/Show', [
            'order' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('purchase_orders.edit');

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()->route('warehouse.purchase-orders.show', $purchaseOrder)->with('error', __('Only draft orders can be edited.'));
        }

        $purchaseOrder->load(['items.product.unit']);

        return Inertia::render('warehouse/purchase-orders/Edit', [
            'order' => $purchaseOrder,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::where('is_active', true)->with('unit')->orderBy('name_tr')->get(),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('error', __('Only draft orders can be edited.'));
        }

        $validated = $request->validated();
        $purchaseOrder->update([
            'supplier_id' => $validated['supplier_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'order_date' => $validated['order_date'],
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $purchaseOrder->items()->delete();
        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $total = (float) $item['quantity'] * (float) $item['unit_price'];
            $subtotal += $total;
            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ]);
        }
        $purchaseOrder->update(['subtotal' => $subtotal, 'tax_amount' => 0, 'total' => $subtotal]);

        return redirect()->route('warehouse.purchase-orders.show', $purchaseOrder)->with('success', __('Purchase order updated.'));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('purchase_orders.delete');

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('error', __('Only draft orders can be deleted.'));
        }

        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return redirect()->route('warehouse.purchase-orders.index')->with('success', __('Purchase order deleted.'));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('purchase_orders.edit');

        if (! in_array($purchaseOrder->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_PARTIAL])) {
            return back()->with('error', __('Order cannot be received in current status.'));
        }

        $purchaseOrder->load('items');
        $userId = $request->user()?->id;

        foreach ($purchaseOrder->items as $item) {
            $qty = (float) $item->quantity;
            StockBalance::firstOrCreate(
                ['warehouse_id' => $purchaseOrder->warehouse_id, 'product_id' => $item->product_id],
                ['quantity' => 0]
            )->increment('quantity', $qty);

            StockMovement::create([
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'product_id' => $item->product_id,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $qty,
                'unit_cost' => $item->unit_price,
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $purchaseOrder->id,
                'notes' => __('Purchase order :number', ['number' => $purchaseOrder->order_number]),
                'user_id' => $userId,
                'movement_date' => now(),
            ]);

            $item->update(['received_quantity' => $item->quantity]);
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'received_date' => now(),
        ]);

        return redirect()->route('warehouse.purchase-orders.show', $purchaseOrder)->with('success', __('Purchase order received.'));
    }
}
