<?php

namespace App\Http\Requests\Modules\Incident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = ['accident', 'incident', 'near_miss', 'unsafe_act', 'unsafe_condition', 'environmental_spill', 'security_breach'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in($categories)],
            'occurred_at' => ['required', 'date'],
            'site_id' => ['required', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'severity_id' => ['required', 'exists:severities,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'description' => ['required', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'involved_persons' => ['nullable', 'array'],
            'involved_persons.*.employee_id' => ['required_with:involved_persons', 'exists:employees,id'],
            'involved_persons.*.note' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', Rule::in(['draft', 'submit'])],
        ];
    }
}
