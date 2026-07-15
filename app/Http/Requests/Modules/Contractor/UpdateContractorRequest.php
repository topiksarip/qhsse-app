<?php

namespace App\Http\Requests\Modules\Contractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contractor.management.update');
    }

    public function rules(): array
    {
        return [
            // Basic Info
            'company_name' => ['required', 'string', 'max:255'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            
            // Contact Info
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            
            // Business Details
            'business_type' => ['nullable', 'string', Rule::in([
                'construction', 'maintenance', 'cleaning', 'security', 
                'transportation', 'consulting', 'technical', 'catering', 'other'
            ])],
            'scope_of_work' => ['nullable', 'string', 'max:1000'],
            'specialization' => ['nullable', 'string', 'max:500'],
            
            // Contract Details
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'contract_status' => ['required', 'string', Rule::in([
                'pending', 'active', 'suspended', 'expired', 'terminated', 'blacklisted'
            ])],
            'contract_terms' => ['nullable', 'string', 'max:2000'],
            
            // QHSSE Requirements
            'safety_induction_required' => ['boolean'],
            'safety_induction_date' => ['nullable', 'date', 'required_if:safety_induction_required,true'],
            'safety_induction_expiry' => ['nullable', 'date', 'after:safety_induction_date'],
            'insurance_required' => ['boolean'],
            'insurance_policy_number' => ['nullable', 'string', 'max:100', 'required_if:insurance_required,true'],
            'insurance_expiry' => ['nullable', 'date', 'required_if:insurance_required,true'],
            
            // Performance
            'performance_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'incident_count' => ['nullable', 'integer', 'min:0'],
            'violation_count' => ['nullable', 'integer', 'min:0'],
            'performance_notes' => ['nullable', 'string', 'max:1000'],
            
            // Site Access
            'authorized_sites' => ['nullable', 'array'],
            'authorized_sites.*' => ['integer', 'exists:sites,id'],
            'authorized_areas' => ['nullable', 'array'],
            'authorized_areas.*' => ['integer', 'exists:areas,id'],
            
            // Documents
            'document_files' => ['nullable', 'array'],
            'document_files.*' => ['string'],
            
            // Approval
            'approval_status' => ['required', 'string', Rule::in([
                'draft', 'submitted', 'approved', 'rejected'
            ])],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan kontraktor wajib diisi.',
            'contact_person.required' => 'Nama contact person wajib diisi.',
            'contact_phone.required' => 'Nomor telepon wajib diisi.',
            'contract_end_date.after_or_equal' => 'Tanggal akhir kontrak harus setelah atau sama dengan tanggal mulai.',
            'safety_induction_date.required_if' => 'Tanggal safety induction wajib diisi jika safety induction diperlukan.',
            'insurance_policy_number.required_if' => 'Nomor polis asuransi wajib diisi jika asuransi diperlukan.',
            'insurance_expiry.required_if' => 'Tanggal kadaluarsa asuransi wajib diisi jika asuransi diperlukan.',
        ];
    }
}
