<?php

namespace App\Http\Requests\Modules\Apd;

use App\Models\Modules\Apd\ApdCatalog;
use App\Models\Modules\Apd\ApdItem;
use App\Modules\Apd\ApdAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiveApdItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive', ApdItem::class);
    }

    public function rules(): array
    {
        $catalog = ApdCatalog::find($this->integer('catalog_id'));

        return [
            'catalog_id' => ['required', 'integer', 'exists:apd_catalogs,id'],
            'track_type' => ['required', Rule::in(['serial', 'batch'])],
            'serial_number' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $this->input('track_type') === 'serial'),
                Rule::unique('apd_items', 'serial_number')->whereNotNull('serial_number')->ignore($this->route('apd_item')?->id),
            ],
            'quantity' => [
                'nullable', 'integer', 'min:1',
                Rule::requiredIf(fn () => $this->input('track_type') === 'batch'),
            ],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'site_id' => [
                'required', 'integer', 'exists:sites,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! app(ApdAccess::class)->canUseLocation($this->user(), (int) $value, $this->integer('department_id') ?: null)) {
                        $fail('Site atau department terpilih di luar ruang lingkup organisasi Anda.');
                    }
                },
            ],
            'area_id' => ['nullable', 'integer', Rule::exists('areas', 'id')->where('site_id', $this->integer('site_id'))],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('site_id', $this->integer('site_id'))],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'condition' => ['sometimes', Rule::in(array_keys(ApdItem::getConditions()))],
            'manufacture_date' => ['nullable', 'date'],
            'purchase_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'catalog_id.required' => 'Katalog APD wajib dipilih.',
            'catalog_id.exists' => 'Katalog APD tidak ditemukan.',
            'track_type.required' => 'Tipe pelacakan wajib dipilih.',
            'serial_number.required' => 'Nomor serial wajib diisi untuk item serial.',
            'serial_number.unique' => 'Nomor serial sudah terdaftar.',
            'quantity.required' => 'Jumlah wajib diisi untuk item batch.',
            'quantity.min' => 'Jumlah minimal 1.',
            'site_id.required' => 'Site wajib dipilih.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'expiry_date.after' => 'Tanggal kedaluwarsa harus di masa depan.',
        ];
    }
}
