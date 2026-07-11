<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\EmergencyPreparedness;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('emergency.contacts.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'site_id' => ['required', 'exists:sites,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kontak wajib diisi.',
            'role.required' => 'Jabatan/peran wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'site_id.required' => 'Site wajib dipilih.',
        ];
    }
}
