import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';

interface Contractor {
    id: number;
    contractor_number: string;
    company_name: string;
    business_registration_number: string | null;
    tax_id: string | null;
    contact_person: string;
    contact_phone: string;
    contact_email: string | null;
    address: string | null;
    business_type: string | null;
    scope_of_work: string | null;
    specialization: string | null;
    contract_start_date: string | null;
    contract_end_date: string | null;
    contract_status: string;
    contract_status_label: string;
    contract_terms: string | null;
    safety_induction_required: boolean;
    safety_induction_date: string | null;
    safety_induction_expiry: string | null;
    insurance_required: boolean;
    insurance_policy_number: string | null;
    insurance_expiry: string | null;
    performance_rating: number | null;
    incident_count: number;
    violation_count: number;
    performance_notes: string | null;
    authorized_sites: number[] | null;
    authorized_areas: number[] | null;
    document_files: string[] | null;
    approval_status: string;
    approval_status_label: string;
    approved_by: { id: number; name: string } | null;
    approved_at: string | null;
    approval_notes: string | null;
    is_active: boolean;
    is_expired: boolean;
    is_approved: boolean;
    is_safety_induction_valid: boolean;
    is_insurance_valid: boolean;
    created_by: { id: number; name: string } | null;
    updated_by: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
}

export default function Show({ auth, contractor }: PageProps<{ contractor: Contractor }>) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Contractor Details
                    </h2>
                    <Link
                        href={route('contractors.edit', contractor.id)}
                        className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        Edit
                    </Link>
                </div>
            }
        >
            <Head title={`Contractor - ${contractor.company_name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Basic Info */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Contractor Number</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.contractor_number}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Company Name</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.company_name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Business Registration</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.business_registration_number || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Tax ID</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.tax_id || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Business Type</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.business_type || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Contract Status</dt>
                                <dd className="mt-1">
                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                        contractor.contract_status === 'active' ? 'bg-green-100 text-green-800' :
                                        contractor.contract_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        contractor.contract_status === 'suspended' ? 'bg-amber-100 text-amber-800' :
                                        'bg-red-100 text-red-800'
                                    }`}>
                                        {contractor.contract_status_label}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {/* Contact Info */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Contact Person</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.contact_person}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Phone</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.contact_phone}</dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Email</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.contact_email || '-'}</dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Address</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.address || '-'}</dd>
                            </div>
                        </dl>
                    </div>

                    {/* Contract Details */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Contract Details</h3>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Start Date</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.contract_start_date || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">End Date</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.contract_end_date || '-'}</dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-sm font-medium text-gray-500">Scope of Work</dt>
                                <dd className="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{contractor.scope_of_work || '-'}</dd>
                            </div>
                        </dl>
                    </div>

                    {/* QHSSE Requirements */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">QHSSE Requirements</h3>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Safety Induction</dt>
                                <dd className="mt-1">
                                    {contractor.safety_induction_required ? (
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                            contractor.is_safety_induction_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        }`}>
                                            {contractor.is_safety_induction_valid ? 'Valid' : 'Expired/Missing'}
                                        </span>
                                    ) : (
                                        <span className="text-sm text-gray-500">Not Required</span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Insurance</dt>
                                <dd className="mt-1">
                                    {contractor.insurance_required ? (
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                            contractor.is_insurance_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                        }`}>
                                            {contractor.is_insurance_valid ? 'Valid' : 'Expired/Missing'}
                                        </span>
                                    ) : (
                                        <span className="text-sm text-gray-500">Not Required</span>
                                    )}
                                </dd>
                            </div>
                            {contractor.safety_induction_expiry && (
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Safety Induction Expiry</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{contractor.safety_induction_expiry}</dd>
                                </div>
                            )}
                            {contractor.insurance_expiry && (
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Insurance Expiry</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{contractor.insurance_expiry}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {/* Performance */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Performance</h3>
                        <dl className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Performance Rating</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.performance_rating ? `${contractor.performance_rating}/5.00` : '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Incident Count</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.incident_count}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Violation Count</dt>
                                <dd className="mt-1 text-sm text-gray-900">{contractor.violation_count}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
