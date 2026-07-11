<?php

namespace App\Http\Requests\Modules\Training;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('training.records.update');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['sometimes', 'exists:employees,id'],
            'training_program_id' => ['sometimes', 'exists:training_programs,id'],
            'provider' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'in:scheduled,in_progress,completed,expired,cancelled'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['nullable', 'string', 'in:pass,fail,pending'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'expiry_date' => ['nullable', 'date', 'after:end_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Karyawan tidak ditemukan.',
            'training_program_id.exists' => 'Program training tidak ditemukan.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
            'certificate_file.max' => 'Ukuran file sertifikat maksimal 5MB.',
            'expiry_date.after' => 'Tanggal kedaluwarsa harus setelah tanggal selesai.',
        ];
    }
}
