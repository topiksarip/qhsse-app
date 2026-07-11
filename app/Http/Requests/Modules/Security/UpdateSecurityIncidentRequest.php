<?php

namespace App\Http\Requests\Modules\Security;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('security.incidents.update');
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:unauthorized_access,theft,vandalism,trespass,suspicious_activity,other'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'site_id' => ['sometimes', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'occurred_at' => ['sometimes', 'date'],
            'severity_id' => ['sometimes', 'exists:severities,id'],
            'status' => ['sometimes', 'string', 'in:reported,under_investigation,closed'],
            'resolution' => ['required_if:status,closed', 'string'],
            'resolved_at' => ['required_if:status,closed', 'date'],
        ];
    }
}
