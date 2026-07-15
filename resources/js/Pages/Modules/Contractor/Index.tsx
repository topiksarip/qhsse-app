import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';

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

interface Site { id: number; name: string; }
interface Filters {
    search?: string; contract_status?: string; approval_status?: string;
    business_type?: string; site_id?: number; date_from?: string; date_to?: string;
    expiring_soon?: boolean; safety_induction_expiring?: boolean; insurance_expiring?: boolean;
}

export default function Index({
    auth, contractors, filters, sites, contractStatuses, approvalStatuses, businessTypes, can,
}: PageProps<{
    contractors: PaginatedContractors; filters: Filters; sites: Site[];
    contractStatuses: Record<string, string>; approvalStatuses: Record<string, string>;
    businessTypes: Record<string, string>; can: { create: boolean; export: boolean };
}>) {
    const [search, setSearch] = useState(filters.search || '');
    const [contractStatus, setContractStatus] = useState(filters.contract_status || '');
    const [approvalStatus, setApprovalStatus] = useState(filters.approval_status || '');
    const [businessType, setBusinessType] = useState(filters.business_type || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('contractors.index'), { search, contract_status: contractStatus, approval_status: approvalStatus, business_type: businessType, site_id: siteId }, { preserveState: true });
    };
    const handleReset = () => {
        setSearch(''); setContractStatus(''); setApprovalStatus(''); setBusinessType(''); setSiteId('');
        router.get(route('contractors.index'));
    };
    const handleExport = () => {
        window.location.href = route('contractors.export', { search, contract_status: contractStatus, approval_status: approvalStatus, business_type: businessType, site_id: siteId });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Kontraktor</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Contractor Management</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && <SecondaryButton size="sm" type="button" onClick={handleExport}>Export</SecondaryButton>}
                        {can.create && <PrimaryButton size="sm" href={route('contractors.create')}>Add Contractor</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Contractors" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <form onSubmit={handleFilter} className="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-5">
                            <div className="lg:col-span-1">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Number, company, contact..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <select value={contractStatus} onChange={(e) => setContractStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">All Statuses</option>
                                {Object.entries(contractStatuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <select value={approvalStatus} onChange={(e) => setApprovalStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">All Statuses</option>
                                {Object.entries(approvalStatuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <select value={businessType} onChange={(e) => setBusinessType(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">All Types</option>
                                {Object.entries(businessTypes).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">All Sites</option>
                                {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                            </select>
                            <div className="flex gap-2 lg:col-span-5">
                                <PrimaryButton type="submit">Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Contractor</th>
                                <th className="px-4 py-3">Contact</th>
                                <th className="px-4 py-3">Contract</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">QHSSE</th>
                                <th className="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {contractors.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada kontraktor"
                                            description="Kelola data kontraktor, kontrak, safety induction, dan compliance QHSSE"
                                            action={can.create ? <PrimaryButton href={route('contractors.create')}>Tambah Kontraktor</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : contractors.data.map((contractor) => (
                                <tr key={contractor.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <div className="text-sm font-medium text-slate-900 dark:text-slate-100">{contractor.company_name}</div>
                                        <div className="text-sm text-gray-500">{contractor.contractor_number}</div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <div className="text-sm text-slate-900 dark:text-slate-100">{contractor.contact_person}</div>
                                        <div className="text-sm text-gray-500">{contractor.contact_phone}</div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <div className="text-sm text-slate-900 dark:text-slate-100">{contractor.contract_start_date || '-'}</div>
                                        <div className="text-sm text-gray-500">{contractor.contract_end_date || '-'}</div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${contractor.contract_status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : contractor.contract_status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'}`}>
                                            {contractor.contract_status_label}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <div className="flex gap-1">
                                            <span className={`inline-flex px-2 py-1 text-xs rounded ${contractor.is_safety_induction_valid ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'}`} title="Safety Induction">SI</span>
                                            <span className={`inline-flex px-2 py-1 text-xs rounded ${contractor.is_insurance_valid ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'}`} title="Insurance">INS</span>
                                        </div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm font-medium">
                                        <Link href={route('contractors.show', contractor.id)} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">View</Link>
                                        <Link href={route('contractors.edit', contractor.id)} className="ml-3 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>
                                        <DeleteWithConfirm routeName="contractors.destroy" id={contractor.id} permission="contractor.management.delete" itemLabel={contractor.company_name} asLink className="ml-3 text-red-600 hover:underline dark:text-red-400">🗑 Hapus</DeleteWithConfirm>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {contractors.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {contractors.links.map((link, index) => (
                                link.url ? (
                                    <Link key={index} href={link.url} className={`px-3 py-1 border rounded ${link.active ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'}`} dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span key={index} className="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:border-gray-700" dangerouslySetInnerHTML={{ __html: link.label }} />
                                )
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
