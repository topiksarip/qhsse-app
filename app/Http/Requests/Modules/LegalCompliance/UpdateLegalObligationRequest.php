<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\LegalCompliance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLegalObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('legal.obligations.update');
    }

    public function rules(): array
    {
        return [
            'obligation_description' => ['required', 'string'],
            'frequency' => ['required', 'string', 'in:monthly,quarterly,annual'],
            'last_completed' => ['nullable', 'date', 'before_or_equal:today'],
            'next_due' => ['nullable', 'date'],
            'evidence_file_id' => ['nullable', 'exists:managed_files,id'],
            'status' => ['required', 'string', 'in:pending,completed'],
        ];
    }

    public function messages(): array
    {
        return [
            'obligation_description.required' => 'Deskripsi kewajiban wajib diisi.',
            'frequency.required' => 'Frekuensi pelaksanaan wajib dipilih.',
            'frequency.in' => 'Frekuensi tidak valid.',
            'last_completed.before_or_equal' => 'Tanggal pelaksanaan tidak boleh di masa depan.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}
