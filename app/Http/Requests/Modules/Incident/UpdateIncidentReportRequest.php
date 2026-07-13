<?php

namespace App\Http\Requests\Modules\Incident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = ['accident', 'incident', 'near_miss', 'unsafe_act', 'unsafe_condition', 'environmental_spill', 'security_breach'];

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', Rule::in($categories)],
            'occurred_at' => ['sometimes', 'date'],
            'site_id' => ['sometimes', Rule::exists('sites', 'id')->where('is_active', true)],
            'area_id' => ['nullable', Rule::exists('areas', 'id')->where('is_active', true)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'severity_id' => ['sometimes', 'exists:severities,id'],
            'priority_id' => ['sometimes', 'exists:priorities,id'],
            'description' => ['sometimes', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'involved_persons' => ['nullable', 'array'],
            'involved_persons.*.employee_id' => ['required_with:involved_persons', 'distinct', Rule::exists('employees', 'id')->where('is_active', true)],
            'involved_persons.*.note' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', Rule::in(['draft', 'submit'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $incident = $this->route('incidentReport');
            $siteId = $this->integer('site_id') ?: (int) $incident?->site_id;
            $relations = ['area_id' => 'areas', 'department_id' => 'departments'];

            foreach ($relations as $field => $table) {
                $id = $this->filled($field) ? $this->integer($field) : null;
                if ($id && ! DB::table($table)->where('id', $id)->where('site_id', $siteId)->exists()) {
                    $validator->errors()->add($field, 'Data yang dipilih tidak berada pada site laporan.');
                }
            }

            foreach ($this->input('involved_persons', []) as $index => $person) {
                $employeeId = (int) ($person['employee_id'] ?? 0);
                if ($employeeId && ! DB::table('employees')->where('id', $employeeId)->where('site_id', $siteId)->exists()) {
                    $validator->errors()->add("involved_persons.{$index}.employee_id", 'Karyawan tidak berada pada site laporan.');
                }
            }
        }];
    }
}
