<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowTransitionRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.workflow.transition') ?? false;
    }

    public function rules(): array
    {
        return [
            'module_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'reference_id' => ['required', 'integer', 'min:1'],
            'action_key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
