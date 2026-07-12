<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\Quality;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Modules\Quality\CustomerComplaint::class);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'product_service' => ['nullable', 'string', 'max:255'],
            'severity_id' => ['required', 'integer', 'exists:severities,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'nama customer',
            'customer_contact' => 'kontak customer',
            'title' => 'judul complaint',
            'description' => 'deskripsi',
            'site_id' => 'site',
            'product_service' => 'produk/layanan',
            'severity_id' => 'severity',
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Deskripsi complaint minimal 20 karakter.',
        ];
    }
}
