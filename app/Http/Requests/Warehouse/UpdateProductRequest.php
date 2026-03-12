<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.edit') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => 'nullable|exists:product_categories,id',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'nullable|numeric',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'stock_balances' => 'nullable|array',
            'stock_balances.*.warehouse_id' => 'required|integer|distinct|exists:warehouses,id',
            'stock_balances.*.quantity' => 'nullable|numeric|min:0',
            'name_tr' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name_tr')->ignore($product->id),
            ],
            'name_en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name_en')->ignore($product->id),
            ],
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100',
            'description_tr' => 'nullable|string',
            'description_en' => 'nullable|string',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'track_quantity' => 'boolean',
            'initial_stock' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unit_id.required' => __('validation.required', ['attribute' => __('products.unit')]),
            'unit_id.exists' => __('validation.exists', ['attribute' => __('products.unit')]),
            'name_tr.required' => __('validation.required', ['attribute' => __('products.nameTr')]),
            'name_tr.unique' => __('products.nameAlreadyExists', ['attribute' => __('products.nameTr')]),
            'name_en.required' => __('validation.required', ['attribute' => __('products.nameEn')]),
            'name_en.unique' => __('products.nameAlreadyExists', ['attribute' => __('products.nameEn')]),
            'sku.unique' => __('validation.unique', ['attribute' => __('products.sku')]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'track_quantity' => $this->boolean('track_quantity', true),
        ]);
    }
}
