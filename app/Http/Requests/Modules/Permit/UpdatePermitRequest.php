<?php

namespace App\Http\Requests\Modules\Permit;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permit.work.update');
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:hot_work,working_at_height,confined_space,electrical,excavation,lifting,other'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'site_id' => ['sometimes', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'contractor_id' => ['nullable', 'exists:companies,id'],
            'work_location' => ['sometimes', 'string', 'max:255'],
            'work_description' => ['sometimes', 'string'],
            'start_datetime' => ['sometimes', 'date'],
            'end_datetime' => ['sometimes', 'date', 'after:start_datetime'],
            'risk_level' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'jsa_reference' => ['nullable', 'string', 'max:255'],
            'worker_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'worker_ids.*' => ['required', 'integer', 'exists:employees,id'],
            'worker_roles' => ['nullable', 'array'],
            'worker_roles.*' => ['nullable', 'array'],
            'worker_roles.*.*' => ['nullable', 'string', 'max:120'],
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['required', 'integer', 'exists:assets,id'],
            'asset_roles' => ['nullable', 'array'],
            'asset_roles.*' => ['nullable', 'array'],
            'asset_roles.*.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_datetime.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'area_id.exists' => 'Area tidak ditemukan.',
            'department_id.exists' => 'Department tidak ditemukan.',
            'contractor_id.exists' => 'Kontraktor tidak ditemukan.',
        ];
    }
}
