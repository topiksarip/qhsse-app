<?php

namespace App\Http\Requests\Modules\DocumentControl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['sop', 'wi', 'jsa', 'hiradc', 'msds', 'policy', 'form', 'manual', 'other'])],
            'version' => ['nullable', 'string', 'max:20'],
            'revision_notes' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
            'review_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:review_date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'is_confidential' => ['sometimes', 'boolean'],
            'file' => ['nullable', 'file', 'max:'.StoreDocumentRequest::DOCUMENT_FILE_MAX_KB, 'mimes:'.StoreDocumentRequest::DOCUMENT_FILE_MIMES],
        ];
    }
}
