<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->input('type');
        $permission = match ($type) {
            'in' => 'stock.in',
            'out' => 'stock.out',
            'transfer' => 'stock.transfer',
            'adjustment' => 'stock.adjustment',
            default => null,
        };

        return $permission && ($this->user()?->can($permission) ?? false);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => 'required_without:rows|exists:warehouses,id',
            'product_id' => 'required_without:rows|exists:products,id',
            'rows' => 'nullable|array',
            'rows.*.product_id' => 'required_with:rows|exists:products,id',
            'rows.*.warehouse_id' => 'required_with:rows|exists:warehouses,id',
            'rows.*.quantity' => [
                'required_with:rows',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($this->input('type') !== 'adjustment' && $value < 0.0001) {
                        $fail(__('validation.min.numeric', ['attribute' => __('common.quantity'), 'min' => '0.0001']));
                    }
                },
            ],
            'supplier_id' => 'nullable|exists:suppliers,id',
            'factor_number' => 'nullable|string|max:255',
            'type' => 'required|in:in,out,transfer,adjustment',
            'quantity' => [
                'required_without:rows',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($this->input('type') !== 'adjustment' && $value < 0.0001) {
                        $fail(__('validation.min.numeric', ['attribute' => __('common.quantity'), 'min' => '0.0001']));
                    }
                },
            ],
            'unit_cost' => 'nullable|numeric|min:0',
            'from_warehouse_id' => 'required_if:type,transfer|nullable|exists:warehouses,id',
            'notes' => 'nullable|string',
            'movement_date' => 'nullable|date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => __('validation.required', ['attribute' => __('stock.warehouse')]),
            'warehouse_id.exists' => __('validation.exists', ['attribute' => __('stock.warehouse')]),
            'product_id.required' => __('validation.required', ['attribute' => __('stock.product')]),
            'product_id.exists' => __('stockMovements.productNotFound'),
            'quantity.required' => __('validation.required', ['attribute' => __('common.quantity')]),
            'quantity.min' => __('validation.min.numeric', ['attribute' => __('common.quantity'), 'min' => '0.0001']),
            'movement_date.required' => __('validation.required', ['attribute' => __('common.date')]),
            'from_warehouse_id.required_if' => __('validation.required', ['attribute' => __('stockMovements.fromWarehouse')]),
            'from_warehouse_id.exists' => __('validation.exists', ['attribute' => __('stockMovements.fromWarehouse')]),
            'supplier_id.exists' => __('validation.exists', ['attribute' => __('suppliers.supplier')]),
            'rows.*.warehouse_id.required_with' => __('validation.required', ['attribute' => __('stock.warehouse')]),
            'rows.*.warehouse_id.exists' => __('validation.exists', ['attribute' => __('stock.warehouse')]),
            'rows.*.product_id.required_with' => __('validation.required', ['attribute' => __('stock.product')]),
            'rows.*.product_id.exists' => __('stockMovements.productNotFound'),
            'rows.*.quantity.required_with' => __('validation.required', ['attribute' => __('common.quantity')]),
            'rows.*.quantity.min' => __('validation.min.numeric', ['attribute' => __('common.quantity'), 'min' => '0.0001']),
        ];
    }
}
