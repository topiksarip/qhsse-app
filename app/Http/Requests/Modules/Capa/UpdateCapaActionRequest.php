<?php

namespace App\Http\Requests\Modules\Capa;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCapaActionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'assigned_to' => ['sometimes', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'severity_id' => ['nullable', 'exists:severities,id'],
            'priority_id' => ['sometimes', 'exists:priorities,id'],
        ];
    }
}
