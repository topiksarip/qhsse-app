<?php

namespace App\Http\Requests\Modules\Inspection;

use Illuminate\Foundation\Http\FormRequest;

class CancelInspectionUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancelled_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
