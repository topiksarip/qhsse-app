import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, SecurityIncident, Site } from '@/types';
import { useState } from 'react';
import TypeBadge from '@/Components/Security/TypeBadge';
import StatusBadge from '@/Components/Security/StatusBadge';
import SeverityBadge from '@/Components/Security/SeverityBadge';

interface IndexProps extends PageProps {
    incidents: PaginatedData<SecurityIncident>;
    filters: {
        search?: string;
        type?: string;
        status?: string;
        site_id?: number;
        date_from?: string;
        date_to?: string;
    };
    sites: Site[];
    types: Record<string, string>;
    statuses: Record<string, string>;
}

function fmtDate(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

export default function Index({ auth, incidents, filters, sites, types, statuses }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [siteId, setSiteId] = useState(filters.site_id ? String(filters.site_id) : '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function buildParams(extra: Record<string, unknown> = {}): Record<string, string> {
        const raw: Record<string, unknown> = {
            search: search || undefined,
            type: type || undefined,
            status: status || undefined,
            site_id: siteId || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            ...extra,
        };
        return Object.fromEntries(
            Object.entries(raw).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]),
        ) as Record<string, string>;
    }

    function applyFilters() {
        router.get(route('security.incidents.index'), buildParams(), { preserveState: true, replace: true });
    }

    function resetFilters() {
        setSearch(''); setType(''); setStatus(''); setSiteId(''); setDateFrom(''); setDateTo('');
        router.get(route('security.incidents.index'), {}, { preserveState: true, replace: true });
    }

    const canCreate = permissions.has('security.incidents.create');
    const canExport = permissions.has('security.incidents.export');
    const canUpdate = permissions.has('security.incidents.update');
    const canEdit = (i: SecurityIncident) => canUpdate && (i.status === 'reported' || i.status === 'under_investigation');

    const typeEntries = Object.entries(types);
    const statusEntries = Object.entries(statuses);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Insiden Keamanan</h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola insiden keamanan: akses tidak sah, pencurian, vandalisme</p>
                    </div>
                    {canCreate && (
                        <Link href={route('security.incidents.create')} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            + Laporkan Insiden
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Insiden Keamanan" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div className="lg:col-span-2">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} placeholder="Cari nomor, judul..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Tipe</option>
                                    {typeEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Status</option>
                                    {statusEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </div>
                            <div>
                                <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </div>
                            <div className="flex items-end gap-2">
                                <button onClick={applyFilters} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                                <button onClick={resetFilters} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Menampilkan {incidents.from ?? 0}–{incidents.to ?? 0} dari {incidents.total} insiden</p>
                        {canExport && (
                            <a href={route('security.incidents.export') + '?' + new URLSearchParams(buildParams()).toString()} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                ⬇ Export CSV
                            </a>
                        )}
                    </div>

                    <div className="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr className="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                    <th className="px-4 py-3">Nomor</th>
                                    <th className="px-4 py-3">Judul</th>
                                    <th className="px-4 py-3">Tipe</th>
                                    <th className="px-4 py-3">Severity</th>
                                    <th className="px-4 py-3 text-center">Status</th>
                                    <th className="px-4 py-3 text-center">Tanggal</th>
                                    <th className="px-4 py-3">Pelapor</th>
                                    <th className="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {incidents.data.length === 0 ? (
                                    <tr><td colSpan={8} className="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada insiden keamanan</td></tr>
                                ) : incidents.data.map((i) => (
                                    <tr key={i.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">{i.security_number}</td>
                                        <td className="max-w-xs px-4 py-3 text-sm">
                                            <Link href={route('security.incidents.show', i.id)} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{i.title}</Link>
                                        </td>
                                        <td className="px-4 py-3"><TypeBadge type={i.type} /></td>
                                        <td className="px-4 py-3"><SeverityBadge severity={i.severity} /></td>
                                        <td className="px-4 py-3 text-center"><StatusBadge status={i.status} /></td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">{fmtDate(i.occurred_at)}</td>
                                        <td className="px-4 py-3 text-sm">{i.reporter?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                            <Link href={route('security.incidents.show', i.id)} className="text-indigo-600 hover:underline dark:text-indigo-400">👁</Link>
                                            {canEdit(i) && <Link href={route('security.incidents.edit', i.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">✏</Link>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {incidents.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            <button disabled={incidents.current_page <= 1} onClick={() => router.get(route('security.incidents.index'), buildParams({ page: incidents.current_page - 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-200 px-3 py-1 text-sm disabled:opacity-50 dark:bg-gray-700">‹ Sebelumnya</button>
                            <span className="text-sm text-gray-600 dark:text-gray-400">{incidents.current_page} / {incidents.last_page}</span>
                            <button disabled={incidents.current_page >= incidents.last_page} onClick={() => router.get(route('security.incidents.index'), buildParams({ page: incidents.current_page + 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-200 px-3 py-1 text-sm disabled:opacity-50 dark:bg-gray-700">Berikutnya ›</button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
