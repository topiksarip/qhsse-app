<?php

namespace App\Http\Requests\Modules\Apd;

use Illuminate\Foundation\Http\FormRequest;

class StoreApdRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('apd.requirements.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'risk_register_id' => ['required', 'exists:risk_registers,id'],
            'apd_catalog_id' => ['required', 'exists:apd_catalogs,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
