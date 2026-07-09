<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class GenerateNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.numbering.generate') ?? false;
    }

    public function rules(): array
    {
        return [
            'module_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'site_code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'reference_type' => ['nullable', 'string', 'max:150'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
