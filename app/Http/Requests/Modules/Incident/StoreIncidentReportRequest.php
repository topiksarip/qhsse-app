<?php

namespace App\Http\Requests\Modules\Incident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = ['accident', 'incident', 'near_miss', 'unsafe_act', 'unsafe_condition', 'environmental_spill', 'security_breach'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in($categories)],
            'occurred_at' => ['required', 'date'],
            'site_id' => ['required', Rule::exists('sites', 'id')->where('is_active', true)],
            'area_id' => ['nullable', Rule::exists('areas', 'id')->where('is_active', true)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'severity_id' => ['required', 'exists:severities,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'description' => ['required', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'ppe_involved' => ['nullable', 'boolean'],
            'apd_item_id' => ['nullable', 'exists:apd_items,id'],
            'ppe_failure' => ['nullable', 'boolean'],
            'ppe_notes' => ['nullable', 'string', 'max:1000'],
            'involved_persons' => ['nullable', 'array'],
            'involved_persons.*.employee_id' => ['required_with:involved_persons', 'distinct', Rule::exists('employees', 'id')->where('is_active', true)],
            'involved_persons.*.note' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', Rule::in(['draft', 'submit'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $siteId = $this->integer('site_id');
            $this->validateSiteRelation($validator, 'area_id', 'areas', $siteId);
            $this->validateSiteRelation($validator, 'department_id', 'departments', $siteId);

            foreach ($this->input('involved_persons', []) as $index => $person) {
                if (! empty($person['employee_id'])) {
                    $this->validateSiteRelation($validator, "involved_persons.{$index}.employee_id", 'employees', $siteId, (int) $person['employee_id']);
                }
            }
        }];
    }

    private function validateSiteRelation(Validator $validator, string $field, string $table, int $siteId, ?int $id = null): void
    {
        $id ??= $this->integer($field);
        if ($id && ! DB::table($table)->where('id', $id)->where('site_id', $siteId)->exists()) {
            $validator->errors()->add($field, 'Data yang dipilih tidak berada pada site laporan.');
        }
    }
}
