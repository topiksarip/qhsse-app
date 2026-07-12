<?php

namespace App\Http\Requests\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ReportTemplate::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['custom'])], // Only custom type can be created
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
            'type.required' => 'Tipe template wajib dipilih.',
            'type.in' => 'Hanya template custom yang dapat dibuat. Template pre-defined sudah tersedia.',
            'config.sections.*.key.required_with' => 'Section key wajib diisi.',
            'config.sections.*.label.required_with' => 'Section label wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'type' => 'custom', // Force custom type
            'is_active' => $this->is_active ?? true,
            'is_predefined' => false, // Custom templates are never predefined
        ]);
    }
}
