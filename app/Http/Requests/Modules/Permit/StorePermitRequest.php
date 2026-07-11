<?php

namespace App\Http\Requests\Modules\Permit;

use Illuminate\Foundation\Http\FormRequest;

class StorePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('permit.work.create');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:hot_work,working_at_height,confined_space,electrical,excavation,lifting,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'site_id' => ['required', 'exists:sites,id'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'contractor_id' => ['nullable', 'exists:companies,id'],
            'work_location' => ['required', 'string', 'max:255'],
            'work_description' => ['required', 'string'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'risk_level' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'jsa_reference' => ['nullable', 'string', 'max:255'],
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
