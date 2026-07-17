<?php

namespace App\Http\Requests\Modules\Inspection;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'inspection_template_id' => ['required', 'exists:inspection_templates,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'inspector_id' => ['required', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['required', 'integer', 'exists:assets,id'],
        ];
    }
}
