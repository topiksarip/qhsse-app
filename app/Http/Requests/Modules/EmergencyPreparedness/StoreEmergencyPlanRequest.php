<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\EmergencyPreparedness;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('emergency.plans.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:fire,medical,spill,evacuation,natural_disaster,security,other'],
            'site_id' => ['required', 'exists:sites,id'],
            'description' => ['required', 'string'],
            'response_procedure' => ['required', 'string'],
            'escalation_procedure' => ['required', 'string'],
            'contact_person_id' => ['required', 'exists:users,id'],
            'emergency_contacts' => ['nullable', 'array'],
            'emergency_contacts.*.name' => ['required_with:emergency_contacts', 'string', 'max:255'],
            'emergency_contacts.*.role' => ['required_with:emergency_contacts', 'string', 'max:255'],
            'emergency_contacts.*.phone' => ['required_with:emergency_contacts', 'string', 'max:50'],
            'equipment_needed' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama rencana darurat wajib diisi.',
            'type.required' => 'Tipe rencana darurat wajib dipilih.',
            'type.in' => 'Tipe rencana darurat tidak valid.',
            'site_id.required' => 'Site wajib dipilih.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'description.required' => 'Deskripsi wajib diisi.',
            'response_procedure.required' => 'Prosedur respons wajib diisi.',
            'escalation_procedure.required' => 'Prosedur eskalasi wajib diisi.',
            'contact_person_id.required' => 'Contact person wajib dipilih.',
            'contact_person_id.exists' => 'Contact person tidak ditemukan.',
        ];
    }
}
