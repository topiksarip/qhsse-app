<?php

namespace App\Http\Requests\Modules\Audit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('audit.findings.create');
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'classification' => ['required', Rule::in(['major_nc', 'minor_nc', 'observation', 'ofi'])],
            'recommendation' => ['nullable', 'string'],
            'capa_action_id' => ['nullable', 'integer', 'exists:capa_actions,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'description' => 'deskripsi temuan',
            'classification' => 'klasifikasi',
            'recommendation' => 'rekomendasi',
            'due_date' => 'tanggal jatuh tempo',
        ];
    }
}
