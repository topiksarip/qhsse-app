<?php

namespace App\Http\Requests\Modules\Incident;

use Illuminate\Foundation\Http\FormRequest;

class IncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('incident_report');
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'severity_id' => ['nullable', 'exists:severities,id'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'approver_id' => ['nullable', 'exists:users,id'],
            'verifier_id' => ['nullable', 'exists:users,id'],
            'event_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:event_date'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', \App\Models\Modules\Incident\IncidentReport::STATUSES)],
            'meta' => ['nullable', 'array'],
        ];
    }
}
