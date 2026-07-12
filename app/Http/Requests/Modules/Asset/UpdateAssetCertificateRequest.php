<?php

namespace App\Http\Requests\Modules\Asset;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $certificate = $this->route('certificate');
        return $this->user()->can('update', $certificate);
    }

    public function rules(): array
    {
        return [
            'certificate_type' => ['sometimes', 'required', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'issuing_body' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:issued_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'certificate_type.required' => 'Certificate type is required.',
            'expiry_date.after' => 'Expiry date must be after issued date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'certificate_type' => 'certificate type',
            'certificate_number' => 'certificate number',
            'issuing_body' => 'issuing body',
            'issued_date' => 'issued date',
            'expiry_date' => 'expiry date',
            'notes' => 'notes',
        ];
    }
}
