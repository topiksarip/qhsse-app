<?php

namespace App\Http\Requests\Modules\Environment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnvironmentalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('environment.records.update');
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['sometimes', 'string', 'in:waste,spill,emission,noise,water_monitoring,other'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'site_id' => ['sometimes', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'occurred_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:recorded,investigated,action_open,closed'],
            'capa_action_id' => ['nullable', 'exists:capa_actions,id'],
        ];

        // Type-specific validation for updates
        $type = $this->input('type');
        
        if ($type === 'waste') {
            $rules['waste_type'] = ['sometimes', 'string', 'max:255'];
            $rules['quantity'] = ['sometimes', 'numeric', 'min:0'];
            $rules['disposal_method'] = ['sometimes', 'string', 'max:255'];
        }

        if ($type === 'spill') {
            $rules['material'] = ['sometimes', 'string', 'max:255'];
            $rules['volume'] = ['sometimes', 'numeric', 'min:0'];
            $rules['containment'] = ['sometimes', 'string', 'max:255'];
        }

        if (in_array($type, ['emission', 'noise', 'water_monitoring'])) {
            $rules['measured_value'] = ['sometimes', 'numeric'];
            $rules['unit'] = ['sometimes', 'string', 'max:50'];
            $rules['limit_value'] = ['sometimes', 'numeric'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'site_id.exists' => 'Site tidak ditemukan.',
            'area_id.exists' => 'Area tidak ditemukan.',
            'capa_action_id.exists' => 'CAPA action tidak ditemukan.',
        ];
    }
}
