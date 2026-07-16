<?php

namespace App\Http\Requests\Modules\Apd;

use App\Models\Modules\Apd\ApdCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApdCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('apd_catalog'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(ApdCatalog::getCategories()))],
            'track_type' => ['required', Rule::in(['serial', 'batch'])],
            'sku' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'standard' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'protection_level' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'default_lifespan_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'inspection_interval_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'default_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama katalog APD wajib diisi.',
            'category.required' => 'Kategori APD wajib dipilih.',
            'category.in' => 'Kategori APD tidak valid.',
            'track_type.required' => 'Tipe pelacakan (serial/batch) wajib dipilih.',
            'track_type.in' => 'Tipe pelacakan tidak valid.',
            'protection_level.in' => 'Level perlindungan tidak valid.',
        ];
    }
}
