import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';

interface CustomerComplaint { id: number; complaint_number: string; customer_name: string; title: string; status: 'open' | 'closed'; site: { id: number; name: string }; severity: { id: number; name: string; color: string }; created_at: string }

interface Props extends PageProps {
    complaints: { data: CustomerComplaint[]; current_page: number; per_page: number; total: number };
    filters: { search?: string; site_id?: number; status?: string; severity_id?: number };
    sites: Array<{ id: number; name: string }>;
    severities: Array<{ id: number; name: string; color: string }>;
    can: { create: boolean; export: boolean };
}

export default function Index({ complaints, filters, sites, severities, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');
    const [status, setStatus] = useState(filters.status || '');
    const [severityId, setSeverityId] = useState(filters.severity_id?.toString() || '');

    const handleFilter = () => router.get(route('quality.complaints.index'), { search, site_id: siteId, status, severity_id: severityId }, { preserveState: true });
    const handleReset = () => { setSearch(''); setSiteId(''); setStatus(''); setSeverityId(''); router.get(route('quality.complaints.index')); };
    const handleExport = () => { window.location.href = route('quality.complaints.export', { search, site_id: siteId, status, severity_id: severityId }); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Kualitas</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Complaint Customer</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && <SecondaryButton size="sm" type="button" onClick={handleExport}>Export CSV</SecondaryButton>}
                        {can.create && <PrimaryButton size="sm" href={route('quality.complaints.create')}>Catat Complaint</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Complaint Customer" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cari</label>
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nomor, nama, judul..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Site</label>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Status</option>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Severity</label>
                                <select value={severityId} onChange={(e) => setSeverityId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Severity</option>
                                    {severities.map((sev) => <option key={sev.id} value={sev.id}>{sev.name}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="button" onClick={handleFilter}>Filter</PrimaryButton>
                            <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                        </div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Customer</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3">Severity</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {complaints.data.length === 0 ? (
                                <tr><td colSpan={7} className="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">Tidak ada data complaint</td></tr>
                            ) : complaints.data.map((complaint) => (
                                <tr key={complaint.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{complaint.complaint_number}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{complaint.customer_name}</td>
                                    <td className="max-w-xs truncate px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{complaint.title}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{complaint.site.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><span className={`rounded-full px-2 py-1 text-xs font-medium bg-${complaint.severity.color}-100 text-${complaint.severity.color}-800`}>{complaint.severity.name}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        {complaint.status === 'open'
                                            ? <span className="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Open</span>
                                            : <span className="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Closed</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('quality.complaints.show', complaint.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Lihat</Link>
                                        {complaint.status === 'open' && can.create && <Link href={route('quality.complaints.edit', complaint.id)} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {complaints.total > complaints.per_page && (
                        <div className="text-sm text-slate-600 dark:text-slate-400">Menampilkan {complaints.data.length} dari {complaints.total} complaint</div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
