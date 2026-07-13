<?php

namespace App\Http\Requests\Modules\Security;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatrolChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patrol'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'area_id' => [
                'nullable',
                'integer',
                Rule::exists('areas', 'id')->where('site_id', $this->input('site_id')),
            ],
            'scheduled_at' => ['required', 'date'],
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'checkpoints' => ['required', 'array', 'min:1', 'max:50'],
            'checkpoints.*.checkpoint' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $user = $this->user();
            if (! $user->can('core.scope.all') && $user->employee?->site_id !== $this->integer('site_id')) {
                $validator->errors()->add('site_id', 'Anda hanya dapat mengubah patroli pada site yang menjadi scope Anda.');
            }
            $officer = User::with('employee')->find($this->integer('assigned_to'));
            if ($officer && (! $officer->hasRole('Security Officer') || ! $officer->can('security.patrols.execute'))) {
                $validator->errors()->add('assigned_to', 'Petugas yang dipilih bukan Security Officer aktif.');
            } elseif ($officer && $officer->employee?->site_id !== $this->integer('site_id')) {
                $validator->errors()->add('assigned_to', 'Petugas harus berasal dari site patroli yang dipilih.');
            }
        }];
    }
}
