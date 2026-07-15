<?php

namespace App\Http\Requests\Modules\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractorPrequalificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contractor.management.update');
    }

    public function rules(): array
    {
        return [
            'prequalified_until' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'prequalified_until.required' => 'Tanggal kedaluwarsa prequalification wajib diisi.',
            'prequalified_until.after' => 'Tanggal kedaluwarsa prequalification harus di masa depan.',
        ];
    }
}
