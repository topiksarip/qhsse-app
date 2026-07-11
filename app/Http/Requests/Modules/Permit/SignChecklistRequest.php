<?php

namespace App\Http\Requests\Modules\Permit;

use Illuminate\Foundation\Http\FormRequest;

class SignChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permit.work.checklist');
    }

    public function rules(): array
    {
        return [
            'checklist_id' => ['required', 'exists:permit_checklists,id'],
            'is_checked' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'checklist_id.exists' => 'Item checklist tidak ditemukan.',
        ];
    }
}
