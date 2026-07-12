<?php

namespace App\Http\Requests\Modules\Asset;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');
        return $this->user()->can('update', $asset);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'in:equipment,machinery,vehicle,safety_equipment,fire_equipment,lifting,other'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'site_id' => ['sometimes', 'required', 'integer', 'exists:sites,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'purchase_date' => ['nullable', 'date'],
            'installation_date' => ['nullable', 'date'],
            'warranty_expiry_date' => ['nullable', 'date', 'after:purchase_date'],
            'status' => ['sometimes', 'string', 'in:active,inactive,decommissioned'],
            'safety_critical' => ['sometimes', 'boolean'],
            'next_inspection_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Asset name is required.',
            'category.required' => 'Asset category is required.',
            'category.in' => 'Invalid asset category.',
            'site_id.required' => 'Site is required.',
            'site_id.exists' => 'Selected site does not exist.',
            'area_id.exists' => 'Selected area does not exist.',
            'department_id.exists' => 'Selected department does not exist.',
            'warranty_expiry_date.after' => 'Warranty expiry date must be after purchase date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'asset name',
            'category' => 'category',
            'serial_number' => 'serial number',
            'model' => 'model',
            'manufacturer' => 'manufacturer',
            'site_id' => 'site',
            'area_id' => 'area',
            'department_id' => 'department',
            'purchase_date' => 'purchase date',
            'installation_date' => 'installation date',
            'warranty_expiry_date' => 'warranty expiry date',
            'status' => 'status',
            'safety_critical' => 'safety critical flag',
            'next_inspection_date' => 'next inspection date',
            'description' => 'description',
            'notes' => 'notes',
        ];
    }
}
