<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\Security;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitorLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visitor = $this->route('visitor');
        return $this->user()->can('update', $visitor);
    }

    public function rules(): array
    {
        return [
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_type' => ['required', 'string', 'in:KTP,SIM,Passport,Lainnya'],
            'visitor_id_number' => ['required', 'string', 'max:100'],
            'visitor_company' => ['nullable', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:20'],
            'host_employee_id' => ['required', 'integer', 'exists:employees,id'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'purpose' => ['required', 'string', 'min:5'],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'checked_in_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'visitor_name' => 'nama pengunjung',
            'visitor_type' => 'jenis ID',
            'visitor_id_number' => 'nomor ID',
            'visitor_company' => 'perusahaan',
            'visitor_phone' => 'telepon',
            'host_employee_id' => 'host',
            'site_id' => 'site',
            'purpose' => 'tujuan kunjungan',
            'vehicle_number' => 'plat kendaraan',
            'checked_in_at' => 'waktu check-in',
            'notes' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'visitor_type.in' => 'Jenis ID harus salah satu dari: KTP, SIM, Passport, atau Lainnya.',
            'purpose.min' => 'Tujuan kunjungan minimal 5 karakter.',
        ];
    }
}
