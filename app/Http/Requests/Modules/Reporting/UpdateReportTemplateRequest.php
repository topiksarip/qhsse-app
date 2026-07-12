<?php

namespace App\Http\Requests\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('report_template');
        return $this->user()->can('update', $template);
    }

    public function rules(): array
    {
        $template = $this->route('report_template');

        // Pre-defined templates: only description and is_active can be updated
        if ($template && $template->is_predefined) {
            return [
                'description' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ];
        }

        // Custom templates: all fields except type and is_predefined
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'config.sections' => ['nullable', 'array'],
            'config.sections.*.key' => ['required_with:config.sections', 'string'],
            'config.sections.*.label' => ['required_with:config.sections', 'string'],
            'config.sections.*.enabled' => ['nullable', 'boolean'],
            'config.sections.*.data_source' => ['nullable', 'string'],
            'config.default_parameters' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama template wajib diisi.',
            'config.sections.*.key.required_with' => 'Section key wajib diisi.',
            'config.sections.*.label.required_with' => 'Section label wajib diisi.',
        ];
    }
}
