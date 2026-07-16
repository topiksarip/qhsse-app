<?php

namespace App\Http\Requests\Modules\Apd;

use App\Models\Modules\Apd\ApdItem;
use App\Modules\Apd\ApdAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApdInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('apd.inspect');
    }

    public function rules(): array
    {
        return [
            'apd_item_id' => ['required', 'exists:apd_items,id'],
            'inspection_type' => ['required', Rule::in(['scheduled', 'incidental', 'manual'])],
            'inspection_date' => ['required', 'date', 'before_or_equal:today'],
            'result' => ['required', Rule::in(['layak', 'tidak_layak'])],
            'condition' => ['nullable', Rule::in(['new', 'good', 'fair', 'poor'])],
            'next_inspection_date' => ['nullable', 'date', 'after:inspection_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();

            $item = ApdItem::find($data['apd_item_id'] ?? null);
            if (! $item) {
                return;
            }

            $user = $this->user();
            if (! app(ApdAccess::class)->canUseLocation($user, (int) $item->site_id, $item->department_id)) {
                $validator->errors()->add('apd_item_id', 'Anda tidak memiliki akses ke item ini.');
            }

            if ($data['result'] === 'tidak_layak' && empty($data['condition']) && $item->condition === 'good') {
                // Condition is optional, but if item still marked good while unfit, that is allowed;
                // keep validation light. No hard error.
            }
        });
    }
}
