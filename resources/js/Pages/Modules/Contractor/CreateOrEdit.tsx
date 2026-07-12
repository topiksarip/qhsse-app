import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent } from 'react';

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
    approval_notes: string | null;
}

interface Site {
    id: number;
    name: string;
}

interface Area {
    id: number;
    site_id: number;
    name: string;
}

export default function CreateOrEdit({
    auth,
    contractor,
    sites,
    areas,
    contractStatuses,
    approvalStatuses,
    businessTypes,
}: PageProps<{
    contractor: Contractor | null;
    sites: Site[];
    areas: Area[];
    contractStatuses: Record<string, string>;
    approvalStatuses: Record<string, string>;
    businessTypes: Record<string, string>;
}>) {
    const { data, setData, post, put, processing, errors } = useForm({
        company_name: contractor?.company_name || '',
        business_registration_number: contractor?.business_registration_number || '',
        tax_id: contractor?.tax_id || '',
        contact_person: contractor?.contact_person || '',
        contact_phone: contractor?.contact_phone || '',
        contact_email: contractor?.contact_email || '',
        address: contractor?.address || '',
        business_type: contractor?.business_type || '',
        scope_of_work: contractor?.scope_of_work || '',
        specialization: contractor?.specialization || '',
        contract_start_date: contractor?.contract_start_date || '',
        contract_end_date: contractor?.contract_end_date || '',
        contract_status: contractor?.contract_status || 'pending',
        contract_terms: contractor?.contract_terms || '',
        safety_induction_required: contractor?.safety_induction_required ?? true,
        safety_induction_date: contractor?.safety_induction_date || '',
        safety_induction_expiry: contractor?.safety_induction_expiry || '',
        insurance_required: contractor?.insurance_required ?? true,
        insurance_policy_number: contractor?.insurance_policy_number || '',
        insurance_expiry: contractor?.insurance_expiry || '',
        performance_rating: contractor?.performance_rating || null,
        incident_count: contractor?.incident_count || 0,
        violation_count: contractor?.violation_count || 0,
        performance_notes: contractor?.performance_notes || '',
        authorized_sites: contractor?.authorized_sites || [],
        authorized_areas: contractor?.authorized_areas || [],
        document_files: contractor?.document_files || [],
        approval_status: contractor?.approval_status || 'draft',
        approval_notes: contractor?.approval_notes || '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (contractor) {
            put(route('contractors.update', contractor.id));
        } else {
            post(route('contractors.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    {contractor ? 'Edit Contractor' : 'Add New Contractor'}
                </h2>
            }
        >
            <Head title={contractor ? 'Edit Contractor' : 'Add Contractor'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Company Name <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.company_name}
                                        onChange={(e) => setData('company_name', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                    {errors.company_name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.company_name}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Business Registration Number
                                    </label>
                                    <input
                                        type="text"
                                        value={data.business_registration_number}
                                        onChange={(e) => setData('business_registration_number', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.business_registration_number && (
                                        <p className="mt-1 text-sm text-red-600">{errors.business_registration_number}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Tax ID / NPWP</label>
                                    <input
                                        type="text"
                                        value={data.tax_id}
                                        onChange={(e) => setData('tax_id', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.tax_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.tax_id}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Business Type</label>
                                    <select
                                        value={data.business_type}
                                        onChange={(e) => setData('business_type', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Select Type</option>
                                        {Object.entries(businessTypes).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                    {errors.business_type && (
                                        <p className="mt-1 text-sm text-red-600">{errors.business_type}</p>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700">Scope of Work</label>
                                    <textarea
                                        value={data.scope_of_work}
                                        onChange={(e) => setData('scope_of_work', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.scope_of_work && (
                                        <p className="mt-1 text-sm text-red-600">{errors.scope_of_work}</p>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700">Specialization</label>
                                    <textarea
                                        value={data.specialization}
                                        onChange={(e) => setData('specialization', e.target.value)}
                                        rows={2}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.specialization && (
                                        <p className="mt-1 text-sm text-red-600">{errors.specialization}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Contact Information */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Contact Person <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.contact_person}
                                        onChange={(e) => setData('contact_person', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                    {errors.contact_person && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contact_person}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">
                                        Contact Phone <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        value={data.contact_phone}
                                        onChange={(e) => setData('contact_phone', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                    {errors.contact_phone && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contact_phone}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Contact Email</label>
                                    <input
                                        type="email"
                                        value={data.contact_email}
                                        onChange={(e) => setData('contact_email', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.contact_email && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contact_email}</p>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700">Address</label>
                                    <textarea
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.address && (
                                        <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Contract Details */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Contract Details</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Contract Start Date</label>
                                    <input
                                        type="date"
                                        value={data.contract_start_date}
                                        onChange={(e) => setData('contract_start_date', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.contract_start_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contract_start_date}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Contract End Date</label>
                                    <input
                                        type="date"
                                        value={data.contract_end_date}
                                        onChange={(e) => setData('contract_end_date', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.contract_end_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contract_end_date}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Contract Status</label>
                                    <select
                                        value={data.contract_status}
                                        onChange={(e) => setData('contract_status', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        {Object.entries(contractStatuses).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                    {errors.contract_status && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contract_status}</p>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700">Contract Terms</label>
                                    <textarea
                                        value={data.contract_terms}
                                        onChange={(e) => setData('contract_terms', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.contract_terms && (
                                        <p className="mt-1 text-sm text-red-600">{errors.contract_terms}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* QHSSE Requirements */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">QHSSE Requirements</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.safety_induction_required}
                                            onChange={(e) => setData('safety_induction_required', e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">Safety Induction Required</span>
                                    </label>
                                </div>

                                {data.safety_induction_required && (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Safety Induction Date</label>
                                            <input
                                                type="date"
                                                value={data.safety_induction_date}
                                                onChange={(e) => setData('safety_induction_date', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            {errors.safety_induction_date && (
                                                <p className="mt-1 text-sm text-red-600">{errors.safety_induction_date}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Safety Induction Expiry</label>
                                            <input
                                                type="date"
                                                value={data.safety_induction_expiry}
                                                onChange={(e) => setData('safety_induction_expiry', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            {errors.safety_induction_expiry && (
                                                <p className="mt-1 text-sm text-red-600">{errors.safety_induction_expiry}</p>
                                            )}
                                        </div>
                                    </>
                                )}

                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.insurance_required}
                                            onChange={(e) => setData('insurance_required', e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">Insurance Required</span>
                                    </label>
                                </div>

                                {data.insurance_required && (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Insurance Policy Number</label>
                                            <input
                                                type="text"
                                                value={data.insurance_policy_number}
                                                onChange={(e) => setData('insurance_policy_number', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            {errors.insurance_policy_number && (
                                                <p className="mt-1 text-sm text-red-600">{errors.insurance_policy_number}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">Insurance Expiry Date</label>
                                            <input
                                                type="date"
                                                value={data.insurance_expiry}
                                                onChange={(e) => setData('insurance_expiry', e.target.value)}
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            {errors.insurance_expiry && (
                                                <p className="mt-1 text-sm text-red-600">{errors.insurance_expiry}</p>
                                            )}
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Performance */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Performance</h3>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Performance Rating (0-5)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="5"
                                        value={data.performance_rating || ''}
                                        onChange={(e) => setData('performance_rating', e.target.value ? parseFloat(e.target.value) : null)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.performance_rating && (
                                        <p className="mt-1 text-sm text-red-600">{errors.performance_rating}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Incident Count</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.incident_count}
                                        onChange={(e) => setData('incident_count', parseInt(e.target.value) || 0)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.incident_count && (
                                        <p className="mt-1 text-sm text-red-600">{errors.incident_count}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Violation Count</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.violation_count}
                                        onChange={(e) => setData('violation_count', parseInt(e.target.value) || 0)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.violation_count && (
                                        <p className="mt-1 text-sm text-red-600">{errors.violation_count}</p>
                                    )}
                                </div>

                                <div className="md:col-span-3">
                                    <label className="block text-sm font-medium text-gray-700">Performance Notes</label>
                                    <textarea
                                        value={data.performance_notes}
                                        onChange={(e) => setData('performance_notes', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.performance_notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.performance_notes}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Approval */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Approval</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Approval Status</label>
                                    <select
                                        value={data.approval_status}
                                        onChange={(e) => setData('approval_status', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        {Object.entries(approvalStatuses).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                    {errors.approval_status && (
                                        <p className="mt-1 text-sm text-red-600">{errors.approval_status}</p>
                                    )}
                                </div>

                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700">Approval Notes</label>
                                    <textarea
                                        value={data.approval_notes}
                                        onChange={(e) => setData('approval_notes', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    {errors.approval_notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.approval_notes}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Submit Buttons */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex items-center justify-end gap-4">
                                <Link
                                    href={route('contractors.index')}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 disabled:opacity-50"
                                >
                                    {processing ? 'Saving...' : 'Save Contractor'}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
