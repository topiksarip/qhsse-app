<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\RiskManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('risk.registers.update');
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['hazard_identification', 'jsa', 'hiradc', 'risk_assessment'])],
            'site_id' => ['sometimes', 'required', 'integer', 'exists:sites,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'activity' => ['sometimes', 'required', 'string', 'max:500'],
            'hazard' => ['sometimes', 'required', 'string'],
            'existing_controls' => ['nullable', 'string'],
            'severity_id' => ['nullable', 'integer', 'exists:severities,id'],
            'probability_id' => ['nullable', 'integer', 'min:1', 'max:5'],
            'risk_level_id' => ['nullable', 'integer', 'exists:risk_matrix_levels,id'],
            'additional_controls' => ['nullable', 'string'],
            'residual_severity_id' => ['nullable', 'integer', 'exists:severities,id'],
            'residual_probability_id' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_risk_level_id' => ['nullable', 'integer', 'exists:risk_matrix_levels,id'],
            'owner_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'review_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi.',
            'type.required' => 'Tipe penilaian wajib dipilih.',
            'type.in' => 'Tipe penilaian tidak valid.',
            'site_id.required' => 'Site wajib dipilih.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'area_id.exists' => 'Area tidak ditemukan.',
            'department_id.exists' => 'Department tidak ditemukan.',
            'activity.required' => 'Aktivitas kerja wajib diisi.',
            'hazard.required' => 'Bahaya (hazard) wajib diisi.',
            'severity_id.exists' => 'Severity tidak ditemukan.',
            'probability_id.min' => 'Probability minimal 1.',
            'probability_id.max' => 'Probability maksimal 5.',
            'risk_level_id.exists' => 'Risk level tidak ditemukan.',
            'owner_id.required' => 'Owner wajib dipilih.',
            'owner_id.exists' => 'Owner tidak ditemukan.',
            'review_date.date' => 'Review date harus berupa tanggal yang valid.',
        ];
    }
}
