<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can("core.{$this->route('type')}.create")
            && in_array($this->route('type'), ['employees', 'sites', 'departments'], true);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
