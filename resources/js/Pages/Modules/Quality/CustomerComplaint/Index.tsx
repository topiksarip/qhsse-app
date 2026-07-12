import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useState } from 'react';

interface CustomerComplaint {
    id: number;
    complaint_number: string;
    customer_name: string;
    customer_contact: string;
    title: string;
    description: string;
    product_service: string | null;
    status: 'open' | 'closed';
    site: { id: number; name: string };
    severity: { id: number; name: string; color: string };
    created_at: string;
}

interface Props extends PageProps {
    complaints: {
        data: CustomerComplaint[];
        current_page: number;
        per_page: number;
        total: number;
    };
    filters: { search?: string; site_id?: number; status?: string; severity_id?: number };
    sites: Array<{ id: number; name: string }>;
    severities: Array<{ id: number; name: string; color: string }>;
    can: { create: boolean; export: boolean };
}

export default function Index({ complaints, filters, sites, severities, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [status, setStatus] = useState(filters.status || '');
    const [severityId, setSeverityId] = useState(filters.severity_id || '');

    const handleFilter = () => {
        router.get(route('quality.complaints.index'), { search, site_id: siteId, status, severity_id: severityId }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch(''); setSiteId(''); setStatus(''); setSeverityId('');
        router.get(route('quality.complaints.index'));
    };

    const handleExport = () => {
        window.location.href = route('quality.complaints.export', { search, site_id: siteId, status, severity_id: severityId });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Complaint Customer" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">Complaint Customer</h1>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">Kelola complaint dari customer</p>
                        </div>
                        <div className="flex gap-3">
                            {can.export && (
                                <button onClick={handleExport} className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Export CSV
                                </button>
                            )}
                            {can.create && (
                                <Link href={route('quality.complaints.create')} className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                                    Catat Complaint
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nomor, nama, judul..." className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Site</label>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white">
                                    <option value="">Semua Site</option>
                                    {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white">
                                    <option value="">Semua Status</option>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Severity</label>
                                <select value={severityId} onChange={(e) => setSeverityId(e.target.value)} className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white">
                                    <option value="">Semua Severity</option>
                                    {severities.map((sev) => <option key={sev.id} value={sev.id}>{sev.name}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="flex gap-3 mt-4">
                            <button onClick={handleFilter} className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Filter</button>
                            <button onClick={handleReset} className="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium">Reset</button>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead className="bg-slate-50 dark:bg-slate-900">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Nomor</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Customer</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Judul</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Site</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Severity</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                    {complaints.data.length === 0 ? (
                                        <tr><td colSpan={7} className="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">Tidak ada data complaint</td></tr>
                                    ) : (
                                        complaints.data.map((complaint) => (
                                            <tr key={complaint.id} className="hover:bg-slate-50 dark:hover:bg-slate-700">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{complaint.complaint_number}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{complaint.customer_name}</td>
                                                <td className="px-6 py-4 text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate">{complaint.title}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{complaint.site.name}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs font-medium rounded-full bg-${complaint.severity.color}-100 text-${complaint.severity.color}-800`}>
                                                        {complaint.severity.name}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {complaint.status === 'open' ? (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Open</span>
                                                    ) : (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Closed</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link href={route('quality.complaints.show', complaint.id)} className="text-blue-600 hover:text-blue-900 dark:text-blue-400">Lihat</Link>
                                                        {complaint.status === 'open' && can.create && (
                                                            <>
                                                                <span className="text-slate-300">|</span>
                                                                <Link href={route('quality.complaints.edit', complaint.id)} className="text-slate-600 hover:text-slate-900 dark:text-slate-400">Edit</Link>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {complaints.total > complaints.per_page && (
                            <div className="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                                <div className="text-sm text-slate-600 dark:text-slate-400">
                                    Menampilkan {complaints.data.length} dari {complaints.total} complaint
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
