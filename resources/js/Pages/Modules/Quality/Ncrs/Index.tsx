import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, Ncr, Site } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
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
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Kualitas</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Non-Conformance Report (NCR)</h2>
                    </div>
                    <div className="flex gap-2">
                        {canExport && (
                            <SecondaryButton size="sm" href={route('quality.ncrs.export') + '?' + new URLSearchParams(buildParams()).toString()}>Export CSV</SecondaryButton>
                        )}
                        {canCreate && (
                            <PrimaryButton size="sm" href={route('quality.ncrs.create')}>Buat NCR</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Non-Conformance Report (NCR)" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div className="lg:col-span-2">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} placeholder="Cari nomor, judul..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                {statusEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={source} onChange={(e) => setSource(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Sumber</option>
                                {sourceEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <div className="flex items-end gap-2">
                                <PrimaryButton type="button" onClick={applyFilters}>Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={resetFilters}>Reset</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Sumber</th>
                                <th className="px-4 py-3">Severity</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Tanggal</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {ncrs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada NCR"
                                            description="Belum ada laporan ketidaksesuaian yang dibuat"
                                            action={canCreate ? <PrimaryButton href={route('quality.ncrs.create')}>Buat NCR Pertama</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : ncrs.data.map((n) => (
                                <tr key={n.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                        <Link href={route('quality.ncrs.show', n.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{n.ncr_number}</Link>
                                    </td>
                                    <td className="max-w-xs px-4 py-3 text-sm">
                                        <Link href={route('quality.ncrs.show', n.id)} className="font-medium text-slate-800 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-400">{n.title}</Link>
                                    </td>
                                    <td className="px-4 py-3"><SourceBadge source={n.source} /></td>
                                    <td className="px-4 py-3"><SeverityBadge severity={n.severity} /></td>
                                    <td className="px-4 py-3 text-center"><StatusBadge status={n.status} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{fmtDate(n.created_at)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{n.site?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('quality.ncrs.show', n.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">👁</Link>
                                        {canEdit(n) && (
                                            <>
                                                <Link href={route('quality.ncrs.edit', n.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">✏</Link>
                                                <DeleteWithConfirm
                                                    routeName="quality.ncrs.destroy"
                                                    id={n.id}
                                                    permission="quality.ncrs.delete"
                                                    itemLabel={n.ncr_number}
                                                    asLink
                                                    className="ml-2 text-red-600 hover:underline dark:text-red-400"
                                                >
                                                    🗑
                                                </DeleteWithConfirm>
                                            </>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {ncrs.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            <button disabled={ncrs.current_page <= 1} onClick={() => router.get(route('quality.ncrs.index'), buildParams({ page: ncrs.current_page - 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">‹ Sebelumnya</button>
                            <span className="text-sm text-gray-600 dark:text-gray-400">{ncrs.current_page} / {ncrs.last_page}</span>
                            <button disabled={ncrs.current_page >= ncrs.last_page} onClick={() => router.get(route('quality.ncrs.index'), buildParams({ page: ncrs.current_page + 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">Berikutnya ›</button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
