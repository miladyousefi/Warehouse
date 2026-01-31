<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('warehouses.edit') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $warehouse = $this->route('warehouse');

        return [
            'name_tr' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name_tr.required' => __('validation.required', ['attribute' => __('warehouses.nameTr')]),
            'name_en.required' => __('validation.required', ['attribute' => __('warehouses.nameEn')]),
            'code.required' => __('validation.required', ['attribute' => __('warehouses.code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('warehouses.code')]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
