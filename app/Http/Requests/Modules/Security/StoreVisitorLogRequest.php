<?php

namespace App\Http\Requests\Modules\Security;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitorLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('security.visitors.create');
    }

    public function rules(): array
    {
        return [
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_type' => ['required', 'string', 'in:vendor,contractor,guest,government,other'],
            'visitor_id_number' => ['nullable', 'string', 'max:50'],
            'visitor_company' => ['nullable', 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:20'],
            'host_employee_id' => ['required', 'exists:employees,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['nullable', 'string', 'max:20'],
            'checked_in_at' => ['sometimes', 'date'],
        ];
    }
}
