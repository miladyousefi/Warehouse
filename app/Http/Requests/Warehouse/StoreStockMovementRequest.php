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
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,transfer,adjustment',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'from_warehouse_id' => 'required_if:type,transfer|nullable|exists:warehouses,id',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => __('validation.required', ['attribute' => __('stock.warehouse')]),
            'product_id.required' => __('validation.required', ['attribute' => __('stock.product')]),
            'quantity.required' => __('validation.required', ['attribute' => __('common.quantity')]),
            'quantity.min' => __('validation.min.numeric', ['attribute' => __('common.quantity'), 'min' => '0.0001']),
            'movement_date.required' => __('validation.required', ['attribute' => __('common.date')]),
            'from_warehouse_id.required_if' => __('validation.required', ['attribute' => __('stockMovements.fromWarehouse')]),
        ];
    }
}
