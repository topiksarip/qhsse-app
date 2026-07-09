<?php

namespace App\Http\Requests\Core;

use App\Core\Files\FileReference;
use Illuminate\Foundation\Http\FormRequest;

class ManagedFileUploadRequest extends FormRequest
{
    public const MAX_FILE_KB = 10240;

    public const ALLOWED_MIMES = [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'csv',
        'txt',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('core.files.upload') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'reference_id' => ['required', 'integer', 'min:1'],
            'collection' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
            'file' => ['required', 'file', 'max:'.self::MAX_FILE_KB, 'mimes:'.implode(',', self::ALLOWED_MIMES)],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function reference(): FileReference
    {
        return new FileReference(
            $this->string('module_name')->toString(),
            $this->integer('reference_id'),
            $this->string('collection', 'default')->toString() ?: 'default',
        );
    }
}
