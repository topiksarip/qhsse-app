<?php

namespace App\Http\Requests\Modules\Security;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecurityIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('security.incidents.create');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:unauthorized_access,theft,vandalism,trespass,suspicious_activity,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'site_id' => ['required', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'occurred_at' => ['required', 'date'],
            'severity_id' => ['required', 'exists:severities,id'],
        ];
    }
}
