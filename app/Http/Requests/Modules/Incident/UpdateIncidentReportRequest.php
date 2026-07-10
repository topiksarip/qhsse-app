<?php

namespace App\Http\Requests\Modules\Incident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = ['accident', 'incident', 'near_miss', 'unsafe_act', 'unsafe_condition', 'environmental_spill', 'security_breach'];

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', Rule::in($categories)],
            'occurred_at' => ['sometimes', 'date'],
            'site_id' => ['sometimes', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'severity_id' => ['sometimes', 'exists:severities,id'],
            'priority_id' => ['sometimes', 'exists:priorities,id'],
            'description' => ['sometimes', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'involved_persons' => ['nullable', 'array'],
            'involved_persons.*.employee_id' => ['required_with:involved_persons', 'exists:employees,id'],
            'involved_persons.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
