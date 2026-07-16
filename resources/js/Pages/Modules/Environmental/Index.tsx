import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, EnvironmentalRecord, Site, EnvironmentalType, EnvironmentalStatus } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import TypeBadge from '@/Components/Environmental/TypeBadge';
import FilterPanel from '@/Components/UI/FilterPanel';
import StatusBadge from '@/Components/Environmental/StatusBadge';
import ExceedanceBadge from '@/Components/Environmental/ExceedanceBadge';
import RecordCard from '@/Components/Environmental/RecordCard';

interface IndexProps extends PageProps {
    records: PaginatedData<EnvironmentalRecord>;
    filters: {
        search?: string;
        type?: string;
        status?: string;
        is_exceedance?: string;
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

export default function Index({ auth, records, filters, sites, types, statuses }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [isExceedance, setIsExceedance] = useState(filters.is_exceedance === '1');
    const [siteId, setSiteId] = useState(filters.site_id ? String(filters.site_id) : '');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function buildParams(extra: Record<string, unknown> = {}): Record<string, string> {
        const raw: Record<string, unknown> = {
            search: search || undefined,
            type: type || undefined,
            status: status || undefined,
            is_exceedance: isExceedance ? '1' : undefined,
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
        router.get(route('environment.records.index'), buildParams(), { preserveState: true, replace: true });
    }

    function resetFilters() {
        setSearch(''); setType(''); setStatus(''); setIsExceedance(false); setSiteId(''); setDateFrom(''); setDateTo('');
        router.get(route('environment.records.index'), {}, { preserveState: true, replace: true });
    }

    const canCreate = permissions.has('environment.records.create');
    const canExport = permissions.has('environment.records.export');
    const canUpdate = permissions.has('environment.records.update');
    const canEdit = (r: EnvironmentalRecord) => canUpdate && (r.status === 'recorded' || r.status === 'investigated');

    const typeEntries = Object.entries(types);
    const statusEntries = Object.entries(statuses);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Lingkungan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Catatan Lingkungan</h2>
                    </div>
                    <div className="flex gap-2">
                        {canExport && (
                            <SecondaryButton size="sm" href={route('environment.records.export') + '?' + new URLSearchParams(buildParams()).toString()}>Export CSV</SecondaryButton>
                        )}
                        {canCreate && (
                            <PrimaryButton size="sm" href={route('environment.records.create')}>Buat Catatan</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Catatan Lingkungan" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, type, status, isExceedance, siteId, dateFrom, dateTo].filter(v => v !== '' && v !== false).length}>
                        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div className="lg:col-span-2">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} placeholder="Cari nomor, judul..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Tipe</option>
                                {typeEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                {statusEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" checked={isExceedance} onChange={(e) => setIsExceedance(e.target.checked)} className="rounded border-slate-300 dark:border-gray-600" />
                                Hanya Exceedance
                            </label>
                            <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <div className="flex items-end gap-2">
                                <PrimaryButton type="button" onClick={applyFilters}>Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={resetFilters}>Reset</SecondaryButton>
                            </div>
                        </div>
                        </div>
                        </FilterPanel>

                        <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Tipe</th>
                                <th className="px-4 py-3 text-right">Nilai</th>
                                <th className="px-4 py-3 text-right">Batas</th>
                                <th className="px-4 py-3 text-center">Exceed</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Tanggal</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3">Reporter</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {records.data.length === 0 ? (
                                <tr>
                                    <td colSpan={11} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada catatan lingkungan"
                                            description="Mulai dokumentasikan pengukuran, pemantauan, dan insiden lingkungan"
                                            action={canCreate ? <PrimaryButton href={route('environment.records.create')}>Buat Catatan</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : records.data.map((r) => (
                                <tr key={r.id} className={r.is_exceedance ? 'border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20' : 'hover:bg-slate-50 dark:hover:bg-gray-800'}>
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                        <Link href={route('environment.records.show', r.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{r.record_number}</Link>
                                    </td>
                                    <td className="max-w-xs px-4 py-3 text-sm">
                                        <Link href={route('environment.records.show', r.id)} className="font-medium text-slate-800 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-400">{r.title}</Link>
                                    </td>
                                    <td className="px-4 py-3"><TypeBadge type={r.type} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-800 dark:text-slate-100">{r.measured_value != null ? `${r.measured_value} ${r.unit ?? ''}` : '—'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-800 dark:text-slate-100">{r.limit_value != null ? `${r.limit_value} ${r.unit ?? ''}` : '—'}</td>
                                    <td className="px-4 py-3 text-center"><ExceedanceBadge isExceedance={r.is_exceedance} /></td>
                                    <td className="px-4 py-3 text-center"><StatusBadge status={r.status} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{fmtDate(r.occurred_at)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{r.site?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{r.reporter?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('environment.records.show', r.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">👁</Link>
                                        {canEdit(r) && (
                                            <>
                                                <Link href={route('environment.records.edit', r.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">✏</Link>
                                                <DeleteWithConfirm
                                                    routeName="environment.records.destroy"
                                                    id={r.id}
                                                    permission="environment.records.delete"
                                                    itemLabel={r.record_number}
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

                    <div className="md:hidden">
                        {records.data.length === 0 ? (
                            <div className="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow dark:bg-gray-800 dark:text-gray-400">🌿 Belum ada catatan lingkungan</div>
                        ) : records.data.map((r) => <RecordCard key={r.id} record={r} />)}
                    </div>

                    {records.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            <button disabled={records.current_page <= 1} onClick={() => router.get(route('environment.records.index'), buildParams({ page: records.current_page - 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">‹ Sebelumnya</button>
                            <span className="text-sm text-gray-600 dark:text-gray-400">{records.current_page} / {records.last_page}</span>
                            <button disabled={records.current_page >= records.last_page} onClick={() => router.get(route('environment.records.index'), buildParams({ page: records.current_page + 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">Berikutnya ›</button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
