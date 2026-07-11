<?php

namespace App\Http\Requests\Modules\Audit;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAuditReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('audit.management.execute');
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'min:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'summary' => 'ringkasan audit',
        ];
    }
}
