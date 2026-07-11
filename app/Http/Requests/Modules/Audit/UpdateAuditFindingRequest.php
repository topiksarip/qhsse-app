<?php

namespace App\Http\Requests\Modules\Audit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuditFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('audit.findings.update');
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:10'],
            'classification' => ['required', Rule::in(['major', 'minor', 'observation'])],
            'recommendation' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
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
