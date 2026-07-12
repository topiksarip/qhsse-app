<?php

namespace App\Http\Requests\Modules\Audit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('audit.management.update');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'audit_type' => ['required', Rule::in(['internal', 'external', 'supplier', 'regulatory'])],
            'scope' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'lead_auditor_id' => ['required', 'exists:users,id'],
            'scheduled_date' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'audit_type' => 'jenis audit',
            'scope' => 'ruang lingkup',
            'department_id' => 'departemen',
            'lead_auditor_id' => 'auditor utama',
            'scheduled_date' => 'tanggal jadwal',
        ];
    }
}
