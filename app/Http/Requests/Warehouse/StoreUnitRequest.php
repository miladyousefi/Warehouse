<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('units.create') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_tr' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'base_unit_id' => 'nullable|exists:units,id',
            'ratio_to_base' => 'nullable|numeric|min:0.0001',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name_tr.required' => __('validation.required', ['attribute' => __('products.nameTr')]),
            'name_en.required' => __('validation.required', ['attribute' => __('products.nameEn')]),
            'symbol.required' => __('validation.required', ['attribute' => __('units.symbol')]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ratio_to_base' => $this->input('ratio_to_base', 1),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
