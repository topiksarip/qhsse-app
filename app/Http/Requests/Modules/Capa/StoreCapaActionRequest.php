<?php

namespace App\Http\Requests\Modules\Capa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCapaActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'source_module' => ['nullable', 'string', Rule::in(['incident', 'inspection', 'asset_inspection', 'audit', 'manual'])],
            'source_reference_id' => ['nullable', 'integer'],
            'source_type' => ['nullable', 'string', Rule::in(['corrective', 'preventive'])],
            'site_id' => ['required', 'exists:sites,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'assigned_to' => ['required', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'severity_id' => ['nullable', 'exists:severities,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
        ];
    }
}
