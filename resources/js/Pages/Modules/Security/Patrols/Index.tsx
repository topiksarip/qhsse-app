import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Option, Patrol, statusClasses, statusLabels } from './types';

interface Props extends PageProps {
    patrols: {
        data: Patrol[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | undefined>;
    sites: Option[];
    statuses: Record<string, string>;
    can: { create: boolean; export: boolean };
}

export default function Index({ patrols, filters, sites, statuses, can }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [siteId, setSiteId] = useState(filters.site_id ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const params = () => ({
        search: search || undefined,
        site_id: siteId || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
    });

    const filter = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('security.patrols.index'), params(), { preserveState: true, replace: true });
    };

    const reset = () => {
        setSearch(''); setSiteId(''); setStatus(''); setDateFrom(''); setDateTo('');
        router.get(route('security.patrols.index'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Patroli Keamanan" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">Patroli Keamanan</h1>
                            <p className="text-sm text-slate-500 dark:text-slate-400">Jadwalkan, eksekusi, dan telusuri hasil checkpoint.</p>
                        </div>
                        <div className="flex gap-2">
                            {can.export && <a href={route('security.patrols.export', params())} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200">Export CSV</a>}
                            {can.create && <Link href={route('security.patrols.create')} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">+ Jadwalkan Patroli</Link>}
                        </div>
                    </header>

                    <form onSubmit={filter} className="rounded-xl bg-white p-4 shadow dark:bg-slate-800">
                        <div className="grid gap-4 md:grid-cols-5">
                            <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau rute..." className="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                                <option value="">Semua Site</option>
                                {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                            </select>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                                <option value="">Semua Status</option>
                                {Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                            <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button className="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white">Terapkan</button>
                            <button type="button" onClick={reset} className="rounded-lg bg-slate-100 px-4 py-2 text-sm text-slate-700 dark:bg-slate-700 dark:text-slate-200">Reset</button>
                        </div>
                    </form>

                    <div className="overflow-hidden rounded-xl bg-white shadow dark:bg-slate-800">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900">
                                    <tr>{['Nomor / Rute', 'Lokasi', 'Petugas', 'Jadwal', 'Progress', 'Status', 'Aksi'].map((item) => <th key={item} className="px-5 py-3">{item}</th>)}</tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200 dark:divide-slate-700">
                                    {patrols.data.map((patrol) => {
                                        const officer = typeof patrol.assigned_to === 'object' ? patrol.assigned_to : null;
                                        return (
                                            <tr key={patrol.id} className="text-sm text-slate-700 dark:text-slate-200">
                                                <td className="px-5 py-4"><div className="font-semibold">{patrol.patrol_number}</div><div className="max-w-xs truncate text-slate-500">{patrol.title}</div></td>
                                                <td className="px-5 py-4">{patrol.site.name}<div className="text-xs text-slate-500">{patrol.area?.name ?? 'Semua area'}</div></td>
                                                <td className="px-5 py-4">{officer?.name ?? '—'}</td>
                                                <td className="px-5 py-4 whitespace-nowrap">{new Date(patrol.scheduled_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</td>
                                                <td className="px-5 py-4"><span className="font-medium">{(patrol.results_count ?? 0) - (patrol.pending_count ?? 0)}/{patrol.results_count ?? 0}</span>{(patrol.issue_count ?? 0) > 0 && <div className="text-xs font-medium text-red-600">{patrol.issue_count} issue</div>}</td>
                                                <td className="px-5 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses[patrol.status]}`}>{statusLabels[patrol.status]}</span></td>
                                                <td className="px-5 py-4"><Link href={route('security.patrols.show', patrol.id)} className="font-medium text-blue-600 hover:underline">Buka</Link></td>
                                            </tr>
                                        );
                                    })}
                                    {patrols.data.length === 0 && <tr><td colSpan={7} className="px-5 py-12 text-center text-sm text-slate-500">Belum ada data patroli.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                        <div className="border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                            <p className="text-sm text-slate-500">Menampilkan {patrols.from ?? 0}–{patrols.to ?? 0} dari {patrols.total}</p>
                            <Pagination links={patrols.links} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
