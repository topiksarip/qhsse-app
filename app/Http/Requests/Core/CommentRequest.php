<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('core.comments.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'module_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'reference_id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'body' => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
        ];
    }
}
