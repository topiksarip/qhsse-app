<?php

namespace App\Http\Requests\Modules\Training;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('training.records.create');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'training_program_id' => ['required', 'exists:training_programs,id'],
            'provider' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Karyawan tidak ditemukan.',
            'training_program_id.exists' => 'Program training tidak ditemukan.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
