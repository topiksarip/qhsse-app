<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\LegalCompliance;

use Illuminate\Foundation\Http\FormRequest;

class StoreLegalObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('legal.obligations.create');
    }

    public function rules(): array
    {
        return [
            'obligation_description' => ['required', 'string'],
            'frequency' => ['required', 'string', 'in:monthly,quarterly,annual'],
            'last_completed' => ['nullable', 'date', 'before_or_equal:today'],
            'next_due' => ['nullable', 'date', 'after:today'],
            'evidence_file_id' => ['nullable', 'exists:managed_files,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'obligation_description.required' => 'Deskripsi kewajiban wajib diisi.',
            'frequency.required' => 'Frekuensi pelaksanaan wajib dipilih.',
            'frequency.in' => 'Frekuensi tidak valid.',
            'last_completed.before_or_equal' => 'Tanggal pelaksanaan tidak boleh di masa depan.',
            'next_due.after' => 'Tanggal jatuh tempo harus di masa depan.',
        ];
    }
}
