<?php

namespace App\Http\Requests\Modules\Communication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');
        return $this->user()->can('update', $campaign);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([
                'safety_alert',
                'lesson_learned',
                'campaign',
                'announcement',
                'newsletter'
            ])],
            'content' => ['required', 'string'],
            'target_audience' => ['required', 'string', Rule::in([
                'all',
                'specific_site',
                'specific_department',
                'specific_role'
            ])],
            'site_id' => [
                Rule::requiredIf(fn() => $this->target_audience === 'specific_site'),
                'nullable',
                'exists:sites,id'
            ],
            'department_id' => [
                Rule::requiredIf(fn() => $this->target_audience === 'specific_department'),
                'nullable',
                'exists:departments,id'
            ],
            'target_role' => [
                Rule::requiredIf(fn() => $this->target_audience === 'specific_role'),
                'nullable',
                'string',
                Rule::in([
                    'Super Admin',
                    'Admin',
                    'QHSSE Manager',
                    'QHSSE Officer',
                    'Supervisor',
                    'Department Head',
                    'Employee / Reporter',
                    'Contractor',
                    'Auditor',
                    'Top Management'
                ])
            ],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul kampanye wajib diisi.',
            'type.required' => 'Tipe kampanye wajib dipilih.',
            'type.in' => 'Tipe kampanye tidak valid.',
            'content.required' => 'Konten kampanye wajib diisi.',
            'target_audience.required' => 'Target audiens wajib dipilih.',
            'target_audience.in' => 'Target audiens tidak valid.',
            'site_id.required' => 'Site wajib dipilih ketika target audiens adalah site tertentu.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'department_id.required' => 'Departemen wajib dipilih ketika target audiens adalah departemen tertentu.',
            'department_id.exists' => 'Departemen tidak ditemukan.',
            'target_role.required' => 'Role wajib dipilih ketika target audiens adalah role tertentu.',
            'target_role.in' => 'Role tidak valid.',
            'expires_at.date' => 'Tanggal kedaluwarsa harus berupa tanggal yang valid.',
            'expires_at.after' => 'Tanggal kedaluwarsa harus setelah hari ini.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ensure conditional fields are null when not applicable
        if ($this->target_audience === 'all') {
            $this->merge([
                'site_id' => null,
                'department_id' => null,
                'target_role' => null,
            ]);
        } elseif ($this->target_audience === 'specific_site') {
            $this->merge([
                'department_id' => null,
                'target_role' => null,
            ]);
        } elseif ($this->target_audience === 'specific_department') {
            $this->merge([
                'site_id' => null,
                'target_role' => null,
            ]);
        } elseif ($this->target_audience === 'specific_role') {
            $this->merge([
                'site_id' => null,
                'department_id' => null,
            ]);
        }
    }
}
