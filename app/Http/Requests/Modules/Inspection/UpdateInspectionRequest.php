<?php

namespace App\Http\Requests\Modules\Inspection;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'results' => ['nullable', 'array'],
            'results.*.inspection_item_id' => ['required_with:results', 'exists:inspection_items,id'],
            'results.*.answer' => ['nullable', 'string', 'max:50'],
            'results.*.remark' => ['nullable', 'string'],
            'results.*.is_unsafe' => ['nullable', 'boolean'],
            'results.*.photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }
}
