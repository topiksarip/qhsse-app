<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NumberingFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('numbering_format')?->id;

        return [
            'module_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/', Rule::unique('numbering_formats', 'module_name')->ignore($id)],
            'prefix' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9]+$/'],
            'padding' => ['required', 'integer', 'min:1', 'max:12'],
            'separator' => ['required', 'string', 'max:5'],
            'reset_frequency' => ['required', Rule::in(['never', 'yearly'])],
            'include_year' => ['sometimes', 'boolean'],
            'include_site_code' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
