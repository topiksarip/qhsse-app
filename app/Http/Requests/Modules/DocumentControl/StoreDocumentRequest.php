<?php

namespace App\Http\Requests\Modules\DocumentControl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public const DOCUMENT_FILE_MAX_KB = 51200;

    public const DOCUMENT_FILE_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('action') === 'submit_review' && blank($this->input('owner_id'))) {
            $this->merge(['owner_id' => $this->user()?->id]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'required_if:action,submit_review', 'string', 'max:255'],
            'type' => ['nullable', 'required_if:action,submit_review', Rule::in(['sop', 'wi', 'jsa', 'hiradc', 'msds', 'policy', 'form', 'manual', 'other'])],
            'version' => ['nullable', 'required_if:action,submit_review', 'string', 'max:20'],
            'revision_notes' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'required_if:action,submit_review', 'date'],
            'review_date' => ['nullable', 'date', 'after_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:review_date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'owner_id' => ['nullable', 'required_if:action,submit_review', 'exists:users,id'],
            'is_confidential' => ['sometimes', 'boolean'],
            'action' => ['nullable', Rule::in(['draft', 'submit_review'])],
            'file' => ['nullable', 'required_if:action,submit_review', 'file', 'max:'.self::DOCUMENT_FILE_MAX_KB, 'mimes:'.self::DOCUMENT_FILE_MIMES],
        ];
    }
}
