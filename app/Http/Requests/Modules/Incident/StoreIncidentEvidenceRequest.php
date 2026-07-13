<?php

namespace App\Http\Requests\Modules\Incident;

use App\Http\Requests\Core\ManagedFileUploadRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('incident.reports.evidence') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.ManagedFileUploadRequest::MAX_FILE_KB,
                'mimes:'.implode(',', ManagedFileUploadRequest::ALLOWED_MIMES),
            ],
        ];
    }
}
