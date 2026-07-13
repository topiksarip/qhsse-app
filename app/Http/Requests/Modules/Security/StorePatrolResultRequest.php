<?php

namespace App\Http\Requests\Modules\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatrolResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('execute', $this->route('patrol'));
    }

    public function rules(): array
    {
        return [
            'result' => ['required', Rule::in(['ok', 'issue', 'na'])],
            'findings' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf($this->input('result') === 'issue'),
                $this->input('result') === 'issue' ? 'min:5' : 'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'findings.required' => 'Temuan wajib diisi jika hasil checkpoint adalah Issue.',
            'findings.min' => 'Temuan Issue minimal 5 karakter.',
        ];
    }
}
