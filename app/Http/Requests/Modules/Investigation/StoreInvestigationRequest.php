<?php

namespace App\Http\Requests\Modules\Investigation;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_id' => ['required', 'exists:incidents,id'],
            'title' => ['required', 'string', 'max:255'],
            'root_cause' => ['nullable', 'string'],
            'five_whys' => ['nullable', 'array'],
            'fishbone' => ['nullable', 'array'],
            'contributing_factors' => ['nullable', 'array'],
            'timeline_events' => ['nullable', 'array'],
            'recommendations' => ['nullable', 'string'],
            'team_members' => ['nullable', 'array'],
            'team_members.*.user_id' => ['required_with:team_members', 'exists:users,id'],
            'team_members.*.role' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'in:draft,start'],
        ];
    }
}
