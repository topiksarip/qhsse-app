import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, Ncr, Site } from '@/types';
import { useState } from 'react';
import SourceBadge from '@/Components/Quality/SourceBadge';
import StatusBadge from '@/Components/Quality/StatusBadge';
import SeverityBadge from '@/Components/Quality/SeverityBadge';

interface IndexProps extends PageProps {
    ncrs: PaginatedData<Ncr>;
    filters: {
        search?: string;
        source?: string;
        status?: string;
        site_id?: number;
        date_from?: string;
        date_to?: string;
    };
    sites: Site[];
    sources: Record<string, string>;
    statuses: Record<string, string>;
}

function fmtDate(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

export default function Index({ auth, ncrs, filters, sites, sources, statuses }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [source, setSource] = useState(filters.source ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [siteId, setSiteId] = useState(filters.site_id ? String(filters.site_id) : '');

    function buildParams(extra: Record<string, unknown> = {}): Record<string, string> {
        const raw: Record<string, unknown> = {
            search: search || undefined,
            source: source || undefined,
            status: status || undefined,
            site_id: siteId || undefined,
            ...extra,
        };
        return Object.fromEntries(
            Object.entries(raw).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]),
        ) as Record<string, string>;
    }

    function applyFilters() {
        router.get(route('quality.ncrs.index'), buildParams(), { preserveState: true, replace: true });
    }

    function resetFilters() {
        setSearch(''); setSource(''); setStatus(''); setSiteId('');
        router.get(route('quality.ncrs.index'), {}, { preserveState: true, replace: true });
    }

    const canCreate = permissions.has('quality.ncrs.create');
    const canExport = permissions.has('quality.ncrs.export');
    const canUpdate = permissions.has('quality.ncrs.update');
    const canEdit = (n: Ncr) => canUpdate && (n.status === 'open' || n.status === 'under_review');

    const sourceEntries = Object.entries(sources);
    const statusEntries = Object.entries(statuses);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Non-Conformance Report (NCR)</h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola laporan ketidaksesuaian dan tindakan korektif</p>
                    </div>
                    {canCreate && (
                        <Link href={route('quality.ncrs.create')} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            + Buat NCR
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Non-Conformance Report (NCR)" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div className="lg:col-span-2">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} placeholder="Cari nomor, judul..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Status</option>
                                    {statusEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={source} onChange={(e) => setSource(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Sumber</option>
                                    {sourceEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <button onClick={applyFilters} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                                <button onClick={resetFilters} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Menampilkan {ncrs.from ?? 0}–{ncrs.to ?? 0} dari {ncrs.total} NCR</p>
                        {canExport && (
                            <a href={route('quality.ncrs.export') + '?' + new URLSearchParams(buildParams()).toString()} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                ⬇ Export CSV
                            </a>
                        )}
                    </div>

                    {ncrs.data.length === 0 ? (
                        <div className="rounded-lg bg-white p-12 text-center shadow dark:bg-gray-800">
                            <p className="text-4xl">📋</p>
                            <p className="mt-3 text-lg font-medium text-gray-700 dark:text-gray-300">Belum ada NCR</p>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada laporan ketidaksesuaian yang dibuat.</p>
                            {canCreate && (
                                <Link href={route('quality.ncrs.create')} className="mt-4 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    + Buat NCR Pertama
                                </Link>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr className="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            <th className="px-4 py-3">Nomor</th>
                                            <th className="px-4 py-3">Judul</th>
                                            <th className="px-4 py-3">Sumber</th>
                                            <th className="px-4 py-3">Severity</th>
                                            <th className="px-4 py-3 text-center">Status</th>
                                            <th className="px-4 py-3 text-center">Tanggal</th>
                                            <th className="px-4 py-3">Site</th>
                                            <th className="px-4 py-3 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {ncrs.data.map((n) => (
                                            <tr key={n.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">{n.ncr_number}</td>
                                                <td className="max-w-xs px-4 py-3 text-sm">
                                                    <Link href={route('quality.ncrs.show', n.id)} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{n.title}</Link>
                                                </td>
                                                <td className="px-4 py-3"><SourceBadge source={n.source} /></td>
                                                <td className="px-4 py-3"><SeverityBadge severity={n.severity} /></td>
                                                <td className="px-4 py-3 text-center"><StatusBadge status={n.status} /></td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm">{fmtDate(n.created_at)}</td>
                                                <td className="px-4 py-3 text-sm">{n.site?.name ?? '-'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                    <Link href={route('quality.ncrs.show', n.id)} className="text-indigo-600 hover:underline dark:text-indigo-400">👁</Link>
                                                    {canEdit(n) && <Link href={route('quality.ncrs.edit', n.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">✏</Link>}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {ncrs.last_page > 1 && (
                                <div className="mt-4 flex items-center justify-center gap-2">
                                    <button disabled={ncrs.current_page <= 1} onClick={() => router.get(route('quality.ncrs.index'), buildParams({ page: ncrs.current_page - 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-200 px-3 py-1 text-sm disabled:opacity-50 dark:bg-gray-700">‹ Sebelumnya</button>
                                    <span className="text-sm text-gray-600 dark:text-gray-400">{ncrs.current_page} / {ncrs.last_page}</span>
                                    <button disabled={ncrs.current_page >= ncrs.last_page} onClick={() => router.get(route('quality.ncrs.index'), buildParams({ page: ncrs.current_page + 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-200 px-3 py-1 text-sm disabled:opacity-50 dark:bg-gray-700">Berikutnya ›</button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
