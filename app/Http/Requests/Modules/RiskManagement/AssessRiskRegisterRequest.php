<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\RiskManagement;

use Illuminate\Foundation\Http\FormRequest;

class AssessRiskRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('risk.registers.assess');
    }

    public function rules(): array
    {
        return [
            'severity_id' => ['required', 'integer', 'exists:severities,id'],
            'probability_id' => ['required', 'integer', 'min:1', 'max:5'],
            'risk_level_id' => ['required', 'integer', 'exists:risk_matrix_levels,id'],
            'additional_controls' => ['nullable', 'string'],
            'residual_severity_id' => ['nullable', 'integer', 'exists:severities,id'],
            'residual_probability_id' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_risk_level_id' => ['nullable', 'integer', 'exists:risk_matrix_levels,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'severity_id.required' => 'Severity wajib dipilih untuk penilaian risiko.',
            'severity_id.exists' => 'Severity tidak ditemukan.',
            'probability_id.required' => 'Probability wajib dipilih untuk penilaian risiko.',
            'probability_id.min' => 'Probability minimal 1.',
            'probability_id.max' => 'Probability maksimal 5.',
            'risk_level_id.required' => 'Risk level wajib ditentukan.',
            'risk_level_id.exists' => 'Risk level tidak ditemukan.',
            'residual_severity_id.exists' => 'Residual severity tidak ditemukan.',
            'residual_probability_id.min' => 'Residual probability minimal 1.',
            'residual_probability_id.max' => 'Residual probability maksimal 5.',
            'residual_risk_level_id.exists' => 'Residual risk level tidak ditemukan.',
        ];
    }
}
