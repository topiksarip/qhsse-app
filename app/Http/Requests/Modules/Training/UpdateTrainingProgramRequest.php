<?php

namespace App\Http\Requests\Modules\Training;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('training.programs.update');
    }

    public function rules(): array
    {
        $programId = $this->route('program');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('training_programs', 'code')->ignore($programId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:safety,technical,compliance,soft_skill,environment,security,quality,first_aid'],
            'duration_hours' => ['required', 'integer', 'min:1'],
            'is_certification' => ['required', 'boolean'],
            'validity_months' => ['nullable', 'integer', 'min:1', 'required_if:is_certification,true'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode program sudah digunakan.',
            'validity_months.required_if' => 'Masa berlaku harus diisi untuk program sertifikasi.',
        ];
    }
}
