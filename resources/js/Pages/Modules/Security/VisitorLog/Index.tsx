import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useState } from 'react';

interface VisitorLog {
    id: number;
    visitor_name: string;
    visitor_company: string | null;
    visitor_type: string;
    visitor_id_number: string;
    visitor_phone: string | null;
    purpose: string;
    vehicle_number: string | null;
    checked_in_at: string;
    checked_out_at: string | null;
    status: 'checked_in' | 'checked_out';
    site: { id: number; name: string };
    host_employee: { id: number; name: string };
    checked_in_by: { id: number; name: string };
    checked_out_by: { id: number; name: string } | null;
}

interface Props extends PageProps {
    visitors: {
        data: VisitorLog[];
        current_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        search?: string;
        site_id?: number;
        status?: string;
        visitor_type?: string;
        from?: string;
        to?: string;
    };
    sites: Array<{ id: number; name: string }>;
    can: {
        create: boolean;
        export: boolean;
    };
}

export default function Index({ auth, visitors, filters, sites, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [status, setStatus] = useState(filters.status || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');

    const handleFilter = () => {
        router.get(route('security.visitors.index'), {
            search, site_id: siteId, status, from, to,
        }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch(''); setSiteId(''); setStatus(''); setFrom(''); setTo('');
        router.get(route('security.visitors.index'));
    };

    const handleExport = () => {
        window.location.href = route('security.visitors.export', { search, site_id: siteId, status, from, to });
    };

    const handleCheckOut = (visitorId: number) => {
        if (confirm('Apakah Anda yakin ingin check-out pengunjung ini?')) {
            router.post(route('security.visitors.check-out', visitorId));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Log Pengunjung" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">Log Pengunjung</h1>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">Kelola check-in dan check-out pengunjung</p>
                        </div>
                        <div className="flex gap-3">
                            {can.export && (
                                <button onClick={handleExport} className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Export CSV
                                </button>
                            )}
                            {can.create && (
                                <Link href={route('security.visitors.create')} className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
                                    Check-In Pengunjung
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nama, perusahaan..." className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white" />
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
                                    <option value="checked_in">Checked In</option>
                                    <option value="checked_out">Checked Out</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dari Tanggal</label>
                                <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sampai Tanggal</label>
                                <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white" />
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
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Nama</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Perusahaan</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Host</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Check-In</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                    {visitors.data.length === 0 ? (
                                        <tr><td colSpan={6} className="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">Tidak ada data pengunjung</td></tr>
                                    ) : (
                                        visitors.data.map((visitor) => (
                                            <tr key={visitor.id} className="hover:bg-slate-50 dark:hover:bg-slate-700">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{visitor.visitor_name}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{visitor.visitor_company || '—'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">{visitor.host_employee.name}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                                    {new Date(visitor.checked_in_at).toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {visitor.status === 'checked_in' ? (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Di Lokasi</span>
                                                    ) : (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Check-Out</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <Link href={route('security.visitors.show', visitor.id)} className="text-blue-600 hover:text-blue-900 dark:text-blue-400">Lihat</Link>
                                                        {visitor.status === 'checked_in' && can.create && (
                                                            <>
                                                                <span className="text-slate-300">|</span>
                                                                <Link href={route('security.visitors.edit', visitor.id)} className="text-slate-600 hover:text-slate-900 dark:text-slate-400">Edit</Link>
                                                                <span className="text-slate-300">|</span>
                                                                <button onClick={() => handleCheckOut(visitor.id)} className="text-green-600 hover:text-green-900 dark:text-green-400">Check-Out</button>
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
                        {visitors.total > visitors.per_page && (
                            <div className="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                                <div className="text-sm text-slate-600 dark:text-slate-400">
                                    Menampilkan {visitors.from} - {visitors.to} dari {visitors.total} data
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
