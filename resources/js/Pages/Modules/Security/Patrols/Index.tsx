import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Option, Patrol, statusClasses, statusLabels } from './types';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';

interface Props extends PageProps {
    patrols: { data: Patrol[]; links: Array<{ url: string | null; label: string; active: boolean }>; from: number | null; to: number | null; total: number };
    filters: Record<string, string | undefined>;
    sites: Option[];
    statuses: Record<string, string>;
    can: { create: boolean; export: boolean; delete: boolean };
}

export default function Index({ patrols, filters, sites, statuses, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [siteId, setSiteId] = useState(filters.site_id ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const params = () => ({ search: search || undefined, site_id: siteId || undefined, status: status || undefined, date_from: dateFrom || undefined, date_to: dateTo || undefined });
    const filter = (event: FormEvent) => { event.preventDefault(); router.get(route('security.patrols.index'), params(), { preserveState: true, replace: true }); };
    const reset = () => { setSearch(''); setSiteId(''); setStatus(''); setDateFrom(''); setDateTo(''); router.get(route('security.patrols.index')); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Keamanan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Patroli Keamanan</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && <a href={route('security.patrols.export', params())} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Export CSV</a>}
                        {can.create && <PrimaryButton size="sm" href={route('security.patrols.create')}>+ Jadwalkan Patroli</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Patroli Keamanan" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <form onSubmit={filter} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
                            <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau rute..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                            </select>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                {Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="submit">Terapkan</PrimaryButton>
                            <SecondaryButton type="button" onClick={reset}>Reset</SecondaryButton>
                        </div>
                    </form>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor / Rute</th>
                                <th className="px-4 py-3">Lokasi</th>
                                <th className="px-4 py-3">Petugas</th>
                                <th className="px-4 py-3">Jadwal</th>
                                <th className="px-4 py-3">Progress</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {patrols.data.map((patrol) => {
                                const officer = typeof patrol.assigned_to === 'object' ? patrol.assigned_to : null;
                                return (
                                    <tr key={patrol.id} className="text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-gray-800">
                                        <td className="px-4 py-3"><div className="font-semibold text-slate-900 dark:text-slate-100">{patrol.patrol_number}</div><div className="max-w-xs truncate text-slate-500">{patrol.title}</div></td>
                                        <td className="px-4 py-3 text-slate-800 dark:text-slate-200">{patrol.site.name}<div className="text-xs text-slate-500">{patrol.area?.name ?? 'Semua area'}</div></td>
                                        <td className="px-4 py-3">{officer?.name ?? '—'}</td>
                                        <td className="whitespace-nowrap px-4 py-3">{new Date(patrol.scheduled_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</td>
                                        <td className="px-4 py-3"><span className="font-medium">{(patrol.results_count ?? 0) - (patrol.pending_count ?? 0)}/{patrol.results_count ?? 0}</span>{(patrol.issue_count ?? 0) > 0 && <div className="text-xs font-medium text-red-600">{patrol.issue_count} issue</div>}</td>
                                        <td className="px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses[patrol.status]}`}>{statusLabels[patrol.status]}</span></td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center"><Link href={route('security.patrols.show', patrol.id)} className="font-medium text-emerald-600 hover:underline dark:text-emerald-400">Buka</Link>
                                            {can.delete && (
                                                <DeleteWithConfirm
                                                    routeName="security.patrols.destroy"
                                                    id={patrol.id}
                                                    permission="security.patrols.delete"
                                                    itemLabel={patrol.patrol_number}
                                                    redirectTo="security.patrols.index"
                                                    asLink
                                                >
                                                    Hapus
                                                </DeleteWithConfirm>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                            {patrols.data.length === 0 && <tr><td colSpan={7} className="px-5 py-12 text-center text-sm text-slate-500">Belum ada data patroli.</td></tr>}
                        </TableBody>
                    </TableWrapper>

                    <div className="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400">
                        <span>Menampilkan {patrols.from ?? 0}–{patrols.to ?? 0} dari {patrols.total}</span>
                        <Pagination links={patrols.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
