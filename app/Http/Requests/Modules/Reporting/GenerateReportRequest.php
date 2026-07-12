<?php

namespace App\Http\Requests\Modules\Reporting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('generate', \App\Models\Modules\Reporting\SavedReport::class);
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'exists:report_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'date_from' => ['required', 'date', 'before_or_equal:date_to'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'format' => ['required', 'string', Rule::in(['csv', 'pdf', 'excel'])],
            'include_charts' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'Template wajib dipilih.',
            'template_id.exists' => 'Template tidak ditemukan.',
            'name.required' => 'Nama laporan wajib diisi.',
            'date_from.required' => 'Tanggal mulai wajib diisi.',
            'date_from.before_or_equal' => 'Tanggal mulai harus sebelum atau sama dengan tanggal akhir.',
            'date_to.required' => 'Tanggal akhir wajib diisi.',
            'date_to.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'date_to.before_or_equal' => 'Tanggal akhir tidak boleh melebihi hari ini.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'department_id.exists' => 'Departemen tidak ditemukan.',
            'format.required' => 'Format laporan wajib dipilih.',
            'format.in' => 'Format laporan harus CSV, PDF, atau Excel.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate date range: maximum 2 years (730 days)
            $from = $this->date('date_from');
            $to = $this->date('date_to');

            if ($from && $to && $from->diffInDays($to) > 730) {
                $validator->errors()->add('date_to', 'Rentang tanggal maksimum adalah 2 tahun (730 hari).');
            }

            // Validate department belongs to site if both provided
            if ($this->site_id && $this->department_id) {
                $department = \App\Models\Core\MasterData\Department::find($this->department_id);
                if ($department && $department->site_id && $department->site_id != $this->site_id) {
                    $validator->errors()->add('department_id', 'Departemen tidak sesuai dengan site yang dipilih.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'include_charts' => $this->include_charts ?? true,
        ]);
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Build parameters array for saving
        $validated['parameters'] = [
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'site_id' => $validated['site_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'include_charts' => $validated['include_charts'],
        ];

        return $validated;
    }
}
