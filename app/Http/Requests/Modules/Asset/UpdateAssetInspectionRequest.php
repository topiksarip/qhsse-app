<?php

namespace App\Http\Requests\Modules\Asset;

use App\Modules\Asset\AssetAccess;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inspection = $this->route('inspection');

        return $this->user()->can('update', $inspection);
    }

    public function rules(): array
    {
        return [
            'inspection_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'inspector_id' => [
                'sometimes', 'required', 'integer', 'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! app(AssetAccess::class)->canInspect($this->route('asset'), (int) $value)) {
                        $fail('Selected inspector is not authorized for this asset site.');
                    }
                },
            ],
            'result' => ['sometimes', 'required', 'string', 'in:pass,fail,maintenance_required'],
            'next_inspection_date' => ['nullable', 'date', 'after:inspection_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'findings' => ['nullable', 'string', 'max:5000'],

        ];
    }

    public function messages(): array
    {
        return [
            'inspection_date.required' => 'Inspection date is required.',
            'inspection_date.before_or_equal' => 'Inspection date cannot be in the future.',
            'inspector_id.required' => 'Inspector is required.',
            'inspector_id.exists' => 'Selected inspector does not exist.',
            'result.required' => 'Inspection result is required.',
            'result.in' => 'Invalid inspection result.',
            'next_inspection_date.after' => 'Next inspection date must be after current inspection date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inspection_date' => 'inspection date',
            'inspector_id' => 'inspector',
            'result' => 'result',
            'next_inspection_date' => 'next inspection date',
            'notes' => 'notes',
            'findings' => 'findings',

        ];
    }
}
