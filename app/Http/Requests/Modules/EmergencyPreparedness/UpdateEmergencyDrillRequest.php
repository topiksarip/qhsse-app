<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\EmergencyPreparedness;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmergencyDrillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('emergency.drills.update');
    }

    public function rules(): array
    {
        return [
            'emergency_plan_id' => ['required', 'exists:emergency_plans,id'],
            'scheduled_date' => ['required', 'date'],
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
            'site_id.required' => 'Site wajib dipilih.',
            'observer_id.required' => 'Observer wajib dipilih.',
        ];
    }
}
