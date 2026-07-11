<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\EmergencyPreparedness;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyDrillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('emergency.drills.create');
    }

    public function rules(): array
    {
        return [
            'emergency_plan_id' => ['required', 'exists:emergency_plans,id'],
            'scheduled_date' => ['required', 'date', 'after:today'],
            'site_id' => ['required', 'exists:sites,id'],
            'observer_id' => ['required', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'emergency_plan_id.required' => 'Rencana darurat wajib dipilih.',
            'emergency_plan_id.exists' => 'Rencana darurat tidak ditemukan.',
            'scheduled_date.required' => 'Tanggal terjadwal wajib diisi.',
            'scheduled_date.after' => 'Tanggal terjadwal harus di masa depan.',
            'site_id.required' => 'Site wajib dipilih.',
            'observer_id.required' => 'Observer wajib dipilih.',
        ];
    }
}
