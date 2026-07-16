<?php

namespace App\Http\Requests\Modules\Apd;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessApdIssuanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Controller checks specific permission (apd.issue / apd.approve) per action.
        return $this->user()->hasPermissionTo('apd.view');
    }

    public function rules(): array
    {
        $action = $this->input('action');

        return [
            'action' => ['required', Rule::in(['return', 'dispose', 'reject'])],
            'condition_in' => [
                Rule::requiredIf($action === 'return'),
                'nullable',
                Rule::in(['new', 'good', 'fair', 'poor', 'damaged']),
            ],
            'returned_date' => ['nullable', 'date'],
            'reason' => [
                Rule::requiredIf(in_array($action, ['dispose', 'reject'], true)),
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
