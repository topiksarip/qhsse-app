<?php

declare(strict_types=1);

namespace App\Http\Requests\Modules\LegalCompliance;

use Illuminate\Foundation\Http\FormRequest;

class StoreLegalRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('legal.register.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'regulation_name' => ['required', 'string', 'max:255'],
            'regulation_number' => ['required', 'string', 'max:255'],
            'issuing_body' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:national,regional,industry,internal'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,non_compliant,in_progress,not_applicable'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'owner_id' => ['required', 'exists:users,id'],
            'next_review_date' => ['nullable', 'date', 'after:today'],
            'document_id' => ['nullable', 'exists:controlled_documents,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul register wajib diisi.',
            'regulation_name.required' => 'Nama regulasi wajib diisi.',
            'regulation_number.required' => 'Nomor regulasi wajib diisi.',
            'issuing_body.required' => 'Instansi penerbit wajib diisi.',
            'category.required' => 'Kategori regulasi wajib dipilih.',
            'category.in' => 'Kategori regulasi tidak valid.',
            'compliance_status.in' => 'Status kepatuhan tidak valid.',
            'owner_id.required' => 'Owner register wajib dipilih.',
            'owner_id.exists' => 'User owner tidak ditemukan.',
            'next_review_date.after' => 'Tanggal review harus di masa depan.',
        ];
    }
}
