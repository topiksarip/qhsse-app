<?php

namespace App\Http\Requests\Modules\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractorEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contractor.management.evaluate');
    }

    public function rules(): array
    {
        return [
            'evaluation_date' => ['required', 'date', 'before_or_equal:today'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'evaluation_date.required' => 'Tanggal evaluasi wajib diisi.',
            'criteria.required' => 'Minimal satu kriteria penilaian wajib diisi.',
            'criteria.min' => 'Minimal satu kriteria penilaian wajib diisi.',
            'criteria.*.integer' => 'Nilai kriteria harus berupa angka.',
            'criteria.*.max' => 'Nilai kriteria maksimal 100.',
        ];
    }
}
