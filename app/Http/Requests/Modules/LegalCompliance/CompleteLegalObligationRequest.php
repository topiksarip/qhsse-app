<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\LegalCompliance;

use Illuminate\Foundation\Http\FormRequest;

class CompleteLegalObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('legal.obligations.update');
    }

    public function rules(): array
    {
        return [
            'last_completed' => ['required', 'date', 'before_or_equal:today'],
            'evidence_file_id' => ['required', 'exists:managed_files,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'last_completed.required' => 'Tanggal pelaksanaan wajib diisi.',
            'last_completed.before_or_equal' => 'Tanggal pelaksanaan tidak boleh di masa depan.',
            'evidence_file_id.required' => 'File bukti pelaksanaan wajib diupload.',
            'evidence_file_id.exists' => 'File bukti tidak ditemukan.',
        ];
    }
}
