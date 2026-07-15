import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';

interface VisitorLog {
    id: number; visitor_name: string; visitor_company: string | null; visitor_type: string; visitor_id_number: string;
    visitor_phone: string | null; purpose: string; vehicle_number: string | null; checked_in_at: string; checked_out_at: string | null;
    status: 'checked_in' | 'checked_out'; site: { id: number; name: string }; host_employee: { id: number; name: string };
    checked_in_by: { id: number; name: string }; checked_out_by: { id: number; name: string } | null;
}

interface Props extends PageProps {
    visitors: { data: VisitorLog[]; current_page: number; per_page: number; total: number; from: number | null; to: number | null };
    filters: { search?: string; site_id?: number; status?: string; visitor_type?: string; from?: string; to?: string };
    sites: Array<{ id: number; name: string }>;
    can: { create: boolean; export: boolean; delete: boolean };
}

export default function Index({ auth, visitors, filters, sites, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');
    const [status, setStatus] = useState(filters.status || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');

    const handleFilter = () => router.get(route('security.visitors.index'), { search, site_id: siteId || undefined, status, from, to }, { preserveState: true });
    const handleReset = () => { setSearch(''); setSiteId(''); setStatus(''); setFrom(''); setTo(''); router.get(route('security.visitors.index')); };
    const handleExport = () => { window.location.href = route('security.visitors.export', { search, site_id: siteId || undefined, status, from, to }); };
    const handleCheckOut = (visitorId: number) => { if (confirm('Apakah Anda yakin ingin check-out pengunjung ini?')) router.post(route('security.visitors.check-out', visitorId)); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Keamanan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Log Pengunjung</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && <SecondaryButton size="sm" type="button" onClick={handleExport}>Export CSV</SecondaryButton>}
                        {can.create && <PrimaryButton size="sm" href={route('security.visitors.create')}>Check-In Pengunjung</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Log Pengunjung" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-5">
                            <div className="lg:col-span-1">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cari</label>
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nama, perusahaan..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
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
                                    <option value="checked_in">Checked In</option>
                                    <option value="checked_out">Checked Out</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Dari Tanggal</label>
                                <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Sampai Tanggal</label>
                                <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
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
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Perusahaan</th>
                                <th className="px-4 py-3">Host</th>
                                <th className="px-4 py-3">Check-In</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {visitors.data.length === 0 ? (
                                <tr><td colSpan={6} className="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">Tidak ada data pengunjung</td></tr>
                            ) : visitors.data.map((visitor) => (
                                <tr key={visitor.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{visitor.visitor_name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{visitor.visitor_company || '—'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{visitor.host_employee.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{new Date(visitor.checked_in_at).toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        {visitor.status === 'checked_in'
                                            ? <span className="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Di Lokasi</span>
                                            : <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Check-Out</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <div className="flex items-center justify-center gap-2">
                                            <Link href={route('security.visitors.show', visitor.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Lihat</Link>
                                            {visitor.status === 'checked_in' && can.create && (
                                                <>
                                                    <Link href={route('security.visitors.edit', visitor.id)} className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>
                                                    <button onClick={() => handleCheckOut(visitor.id)} className="text-green-600 hover:underline dark:text-green-400">Check-Out</button>
                                                </>
                                            )}
                                            {can.delete && (
                                                <DeleteWithConfirm
                                                    routeName="security.visitors.destroy"
                                                    id={visitor.id}
                                                    permission="security.visitors.delete"
                                                    itemLabel={visitor.visitor_name}
                                                    redirectTo="security.visitors.index"
                                                    asLink
                                                >
                                                    Hapus
                                                </DeleteWithConfirm>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {visitors.total > visitors.per_page && (
                        <div className="text-sm text-gray-600 dark:text-gray-400">Menampilkan {visitors.from} – {visitors.to} dari {visitors.total} data</div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
