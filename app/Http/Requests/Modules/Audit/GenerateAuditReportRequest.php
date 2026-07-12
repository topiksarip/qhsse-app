<?php

namespace App\Http\Requests\Modules\Audit;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAuditReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $audit = $this->route('audit');

        // Check permission
        if (! $this->user()->can('audit.management.execute')) {
            return false;
        }

        // Check audit status - must be in_progress to generate report
        if ($audit && $audit->status !== 'in_progress') {
            abort(403, 'Audit harus berstatus in_progress untuk membuat laporan.');
        }

        return true;
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
