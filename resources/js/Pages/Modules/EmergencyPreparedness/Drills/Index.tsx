import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyDrill, EmergencyPlan, Site, PageProps, PaginatedData } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';

interface DrillIndexProps extends PageProps {
    drills: PaginatedData<EmergencyDrill & { emergency_plan: EmergencyPlan }>;
    filters: { search?: string; status?: string; result?: string; site_id?: number; from?: string; to?: string };
    sites: Site[];
    can: { create: boolean; export: boolean; execute: boolean; delete: boolean };
}

const drillStatusColors: Record<string, string> = { scheduled: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', executed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' };
const drillStatusLabels: Record<string, string> = { scheduled: 'Terjadwal', executed: 'Selesai' };
const drillResultColors: Record<string, string> = { pass: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', fail: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', needs_improvement: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' };
const drillResultLabels: Record<string, string> = { pass: 'Lulus', fail: 'Gagal', needs_improvement: 'Perlu Perbaikan' };

export default function Index({ auth, drills, filters, sites, can }: DrillIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [result, setResult] = useState(filters.result || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');
    const [fromDate, setFromDate] = useState(filters.from || '');
    const [toDate, setToDate] = useState(filters.to || '');

    const handleFilter = () => router.get(route('emergency.drills.index'), { search: search || undefined, status: status || undefined, result: result || undefined, site_id: siteId || undefined, from: fromDate || undefined, to: toDate || undefined }, { preserveState: true, preserveScroll: true });
    const handleReset = () => { setSearch(''); setStatus(''); setResult(''); setSiteId(''); setFromDate(''); setToDate(''); router.get(route('emergency.drills.index')); };
    const handleExport = () => { window.location.href = route('emergency.drills.export', filters); };

    const statusBadge = (s: string) => <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${drillStatusColors[s] || drillStatusColors.scheduled}`}>{drillStatusLabels[s] || s}</span>;
    const resultBadge = (r: 'pass' | 'fail' | 'needs_improvement' | null) => {
        if (!r) return <span className="text-gray-400">—</span>;
        return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${drillResultColors[r] || 'bg-gray-100 text-gray-800'}`}>{drillResultLabels[r] || r}</span>;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-red-600 dark:text-red-400">Darurat</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Latihan Darurat</h2>
                    </div>
                    {can.create && <PrimaryButton size="sm" href={route('emergency.drills.create')} className="bg-red-600 hover:bg-red-700 focus:ring-red-500">+ Jadwalkan Latihan</PrimaryButton>}
                </div>
            }
        >
            <Head title="Latihan Darurat" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <input type="text" placeholder="🔍 Cari nomor..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && handleFilter()} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                        </div>
                        <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                <option value="scheduled">Terjadwal</option>
                                <option value="executed">Selesai</option>
                            </select>
                            <select value={result} onChange={(e) => setResult(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Hasil</option>
                                <option value="pass">Lulus</option>
                                <option value="fail">Gagal</option>
                                <option value="needs_improvement">Perlu Perbaikan</option>
                            </select>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <div className="grid grid-cols-2 gap-2">
                                <input type="date" value={fromDate} onChange={(e) => setFromDate(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                                <input type="date" value={toDate} onChange={(e) => setToDate(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="button" onClick={handleFilter}>Filter</PrimaryButton>
                            <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                        </div>
                    </div>

                    <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Menampilkan {drills.from} – {drills.to} dari {drills.total} latihan</span>
                        {can.export && <SecondaryButton size="sm" type="button" onClick={handleExport}>⬇ Export CSV</SecondaryButton>}
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Rencana</th>
                                <th className="px-4 py-3 text-center">Terjadwal</th>
                                <th className="px-4 py-3 text-center">Eksekusi</th>
                                <th className="px-4 py-3 text-center">Hasil</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {drills.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState title="Belum ada latihan darurat" description="Jadwalkan dan dokumentasi latihan kebakaran, evakuasi, dan kesiapsiagaan darurat" action={can.create ? <PrimaryButton href={route('emergency.drills.create')} className="bg-red-600 hover:bg-red-700 focus:ring-red-500">+ Jadwalkan Latihan Pertama</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : drills.data.map((drill) => (
                                <tr key={drill.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                        <Link href={route('emergency.drills.show', drill.id)} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{drill.drill_number}</Link>
                                    </td>
                                    <td className="max-w-xs truncate px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{drill.emergency_plan?.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{drill.scheduled_date}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{drill.executed_date || '—'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">{resultBadge(drill.result ?? null)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">{statusBadge(drill.status)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('emergency.drills.show', drill.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Lihat</Link>
                                        {drill.status === 'scheduled' && can.create && <Link href={route('emergency.drills.edit', drill.id)} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>}
                                        {drill.status === 'scheduled' && can.execute && <Link href={route('emergency.drills.execute', drill.id)} className="ml-2 text-green-600 hover:underline dark:text-green-400">Eksekusi</Link>}
                                        {can.delete && (
                                            <DeleteWithConfirm
                                                routeName="emergency.drills.destroy"
                                                id={drill.id}
                                                permission="emergency.drills.delete"
                                                itemLabel={drill.drill_number}
                                                redirectTo="emergency.drills.index"
                                                asLink
                                            >
                                                Delete
                                            </DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {drills.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {drills.current_page > 1 && <Link href={route('emergency.drills.index', { ...filters, page: drills.current_page - 1 })} className="rounded-md border border-slate-300 px-3 py-1 text-sm text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">‹ Sebelumnya</Link>}
                            {[...Array(drills.last_page)].map((_, i) => (
                                <Link key={i + 1} href={route('emergency.drills.index', { ...filters, page: i + 1 })} className={`rounded-md border px-3 py-1 text-sm ${drills.current_page === i + 1 ? 'border-red-600 bg-red-600 text-white' : 'border-slate-300 text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800'}`}>{i + 1}</Link>
                            ))}
                            {drills.current_page < drills.last_page && <Link href={route('emergency.drills.index', { ...filters, page: drills.current_page + 1 })} className="rounded-md border border-slate-300 px-3 py-1 text-sm text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Berikutnya ›</Link>}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
