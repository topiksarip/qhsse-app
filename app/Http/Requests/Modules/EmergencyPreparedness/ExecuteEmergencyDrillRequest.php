<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\EmergencyPreparedness;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteEmergencyDrillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('emergency.drills.execute');
    }

    public function rules(): array
    {
        return [
            'executed_date' => ['required', 'date', 'before_or_equal:today'],
            'participants_count' => ['required', 'integer', 'min:1'],
            'result' => ['required', 'string', 'in:pass,fail,needs_improvement'],
            'findings' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'executed_date.required' => 'Tanggal pelaksanaan wajib diisi.',
            'executed_date.before_or_equal' => 'Tanggal pelaksanaan tidak boleh di masa depan.',
            'participants_count.required' => 'Jumlah peserta wajib diisi.',
            'participants_count.min' => 'Jumlah peserta minimal 1 orang.',
            'result.required' => 'Hasil latihan wajib dipilih.',
            'result.in' => 'Hasil latihan tidak valid.',
        ];
    }
}
