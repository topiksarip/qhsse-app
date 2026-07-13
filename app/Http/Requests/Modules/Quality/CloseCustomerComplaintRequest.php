<?php

namespace App\Http\Requests\Modules\Quality;

use Illuminate\Foundation\Http\FormRequest;

class CloseCustomerComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('complaint'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
