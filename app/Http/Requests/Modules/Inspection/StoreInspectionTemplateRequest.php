<?php

namespace App\Http\Requests\Modules\Inspection;

use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:safety,environment,equipment,fire,housekeeping,security,quality,compliance'],
            'items' => ['nullable', 'array'],
            'items.*.question' => ['required_with:items', 'string'],
            'items.*.type' => ['required_with:items', 'string', 'in:yes_no,safe_unsafe,na,scale,text,yes_no_na'],
            'items.*.category' => ['nullable', 'string'],
            'items.*.is_required' => ['nullable', 'boolean'],
            'items.*.order' => ['nullable', 'integer'],
        ];
    }
}
