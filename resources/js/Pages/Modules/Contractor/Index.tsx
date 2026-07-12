import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';

interface Contractor {
    id: number;
    contractor_number: string;
    company_name: string;
    contact_person: string;
    contact_phone: string;
    business_type: string | null;
    contract_status: string;
    contract_status_label: string;
    contract_start_date: string | null;
    contract_end_date: string | null;
    approval_status: string;
    approval_status_label: string;
    is_safety_induction_valid: boolean;
    is_insurance_valid: boolean;
    performance_rating: number | null;
    incident_count: number;
    created_at: string;
}

interface PaginatedContractors {
    data: Contractor[];
    links: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Site {
    id: number;
    name: string;
}

interface Filters {
    search?: string;
    contract_status?: string;
    approval_status?: string;
    business_type?: string;
    site_id?: number;
    date_from?: string;
    date_to?: string;
    expiring_soon?: boolean;
    safety_induction_expiring?: boolean;
    insurance_expiring?: boolean;
}

export default function Index({
    auth,
    contractors,
    filters,
    sites,
    contractStatuses,
    approvalStatuses,
    businessTypes,
    can,
}: PageProps<{
    contractors: PaginatedContractors;
    filters: Filters;
    sites: Site[];
    contractStatuses: Record<string, string>;
    approvalStatuses: Record<string, string>;
    businessTypes: Record<string, string>;
    can: { create: boolean; export: boolean };
}>) {
    const [search, setSearch] = useState(filters.search || '');
    const [contractStatus, setContractStatus] = useState(filters.contract_status || '');
    const [approvalStatus, setApprovalStatus] = useState(filters.approval_status || '');
    const [businessType, setBusinessType] = useState(filters.business_type || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('contractors.index'), {
            search,
            contract_status: contractStatus,
            approval_status: approvalStatus,
            business_type: businessType,
            site_id: siteId,
        }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        setContractStatus('');
        setApprovalStatus('');
        setBusinessType('');
        setSiteId('');
        router.get(route('contractors.index'));
    };

    const handleExport = () => {
        window.location.href = route('contractors.export', {
            search,
            contract_status: contractStatus,
            approval_status: approvalStatus,
            business_type: businessType,
            site_id: siteId,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Contractor Management
                    </h2>
                    <div className="flex gap-2">
                        {can.export && (
                            <button
                                onClick={handleExport}
                                className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                            >
                                Export
                            </button>
                        )}
                        {can.create && (
                            <Link
                                href={route('contractors.create')}
                                className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                            >
                                Add Contractor
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Contractors" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                        <form onSubmit={handleFilter} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Search</label>
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Number, company, contact..."
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Contract Status</label>
                                    <select
                                        value={contractStatus}
                                        onChange={(e) => setContractStatus(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">All Statuses</option>
                                        {Object.entries(contractStatuses).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Approval Status</label>
                                    <select
                                        value={approvalStatus}
                                        onChange={(e) => setApprovalStatus(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">All Statuses</option>
                                        {Object.entries(approvalStatuses).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Business Type</label>
                                    <select
                                        value={businessType}
                                        onChange={(e) => setBusinessType(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">All Types</option>
                                        {Object.entries(businessTypes).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Site</label>
                                    <select
                                        value={siteId}
                                        onChange={(e) => setSiteId(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">All Sites</option>
                                        {sites.map((site) => (
                                            <option key={site.id} value={site.id}>{site.name}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="submit"
                                    className="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                                >
                                    Filter
                                </button>
                                <button
                                    type="button"
                                    onClick={handleReset}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                >
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contractor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contact
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contract
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            QHSSE
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {contractors.data.map((contractor) => (
                                        <tr key={contractor.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {contractor.company_name}
                                                </div>
                                                <div className="text-sm text-gray-500">
                                                    {contractor.contractor_number}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{contractor.contact_person}</div>
                                                <div className="text-sm text-gray-500">{contractor.contact_phone}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{contractor.contract_start_date || '-'}</div>
                                                <div className="text-sm text-gray-500">{contractor.contract_end_date || '-'}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                    contractor.contract_status === 'active' ? 'bg-green-100 text-green-800' :
                                                    contractor.contract_status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {contractor.contract_status_label}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex gap-1">
                                                    <span className={`inline-flex px-2 py-1 text-xs rounded ${
                                                        contractor.is_safety_induction_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                    }`} title="Safety Induction">
                                                        SI
                                                    </span>
                                                    <span className={`inline-flex px-2 py-1 text-xs rounded ${
                                                        contractor.is_insurance_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                    }`} title="Insurance">
                                                        INS
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <Link
                                                    href={route('contractors.show', contractor.id)}
                                                    className="text-indigo-600 hover:text-indigo-900 mr-3"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    href={route('contractors.edit', contractor.id)}
                                                    className="text-gray-600 hover:text-gray-900"
                                                >
                                                    Edit
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {contractors.data.length > 0 && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm text-gray-700">
                                        Showing <span className="font-medium">{contractors.data.length}</span> of{' '}
                                        <span className="font-medium">{contractors.total}</span> results
                                    </div>
                                    <div className="flex gap-2">
                                        {contractors.links.map((link, index) => (
                                            link.url ? (
                                                <Link
                                                    key={index}
                                                    href={link.url}
                                                    className={`px-3 py-1 border rounded ${
                                                        link.active
                                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ) : (
                                                <span
                                                    key={index}
                                                    className="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-200"
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            )
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
