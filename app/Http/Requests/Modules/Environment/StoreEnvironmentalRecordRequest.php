<?php

namespace App\Http\Requests\Modules\Environment;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnvironmentalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('environment.records.create');
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', 'string', 'in:waste,spill,emission,noise,water_monitoring,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'site_id' => ['required', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'occurred_at' => ['nullable', 'date'],
        ];

        // Type-specific validation
        $type = $this->input('type');
        
        if ($type === 'waste') {
            $rules['waste_type'] = ['required', 'string', 'max:255'];
            $rules['quantity'] = ['required', 'numeric', 'min:0'];
            $rules['disposal_method'] = ['required', 'string', 'max:255'];
        }

        if ($type === 'spill') {
            $rules['material'] = ['required', 'string', 'max:255'];
            $rules['volume'] = ['required', 'numeric', 'min:0'];
            $rules['containment'] = ['required', 'string', 'max:255'];
        }

        if (in_array($type, ['emission', 'noise', 'water_monitoring'])) {
            $rules['measured_value'] = ['required', 'numeric'];
            $rules['unit'] = ['required', 'string', 'max:50'];
            $rules['limit_value'] = ['required', 'numeric'];
        }

        if ($type === 'emission' || $type === 'water_monitoring') {
            $rules['parameter'] = ['required', 'string', 'max:255'];
        }

        if ($type === 'noise') {
            $rules['location'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'site_id.exists' => 'Site tidak ditemukan.',
            'area_id.exists' => 'Area tidak ditemukan.',
        ];
    }
}
