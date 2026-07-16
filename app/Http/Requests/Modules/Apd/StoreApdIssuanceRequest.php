<?php

namespace App\Http\Requests\Modules\Apd;

use App\Models\Core\MasterData\Area;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Modules\Apd\ApdAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApdIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('apd.create')
            || $this->user()->hasPermissionTo('apd.request');
    }

    public function rules(): array
    {
        return [
            'apd_item_id' => ['required', 'exists:apd_items,id'],
            'holder_type' => ['required', Rule::in(['employee', 'contractor', 'location'])],
            'holder_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'condition_out' => ['nullable', Rule::in(['new', 'good', 'fair', 'poor'])],
            'issue_date' => ['nullable', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // request-only fields
            'requested_date' => ['nullable', 'date'],
            'start_as_request' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();

            if (! $this->holderExists($data['holder_type'], (int) $data['holder_id'])) {
                $validator->errors()->add('holder_id', 'Pemegang tidak ditemukan untuk tipe yang dipilih.');
            }

            $item = \App\Models\Modules\Apd\ApdItem::find($data['apd_item_id'] ?? null);
            if ($item) {
                $user = $this->user();
                if (! app(ApdAccess::class)->canUseLocation($user, (int) $item->site_id, $item->department_id)) {
                    $validator->errors()->add('apd_item_id', 'Anda tidak memiliki akses ke lokasi stok item ini.');
                }
                if ($item->track_type === 'serial' && ($data['quantity'] ?? 1) > 1) {
                    $validator->errors()->add('quantity', 'Item serial hanya boleh quantity 1.');
                }
            }
        });
    }

    private function holderExists(string $type, int $id): bool
    {
        return match ($type) {
            'employee' => Employee::whereKey($id)->exists(),
            'contractor' => \App\Models\Modules\Contractor\Contractor::whereKey($id)->exists(),
            'location' => Area::whereKey($id)->exists(),
            default => false,
        };
    }
}
