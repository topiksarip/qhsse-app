import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, Permit, Site, Department } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import PermitTypeBadge from '@/Components/Permit/PermitTypeBadge';
import FilterPanel from '@/Components/UI/FilterPanel';
import StatusBadge from '@/Components/Permit/StatusBadge';
import RiskBadge from '@/Components/Permit/RiskBadge';
import ValidityBadge from '@/Components/Permit/ValidityBadge';

interface IndexProps extends PageProps {
    permits: PaginatedData<Permit>;
    filters: {
        search?: string;
        site_id?: string;
        department_id?: string;
        type?: string;
        status?: string;
        risk_level?: string;
        validity_status?: string;
    };
    sites: Site[];
    departments: Department[];
    types: Record<string, string>;
    statuses: Record<string, string>;
    riskLevels: Record<string, string>;
}

function validityOf(p: Permit): 'active' | 'expired' | 'expiring_soon' | 'not_started' {
    const now = new Date();
    const start = new Date(p.start_datetime);
    const end = new Date(p.end_datetime);
    if (p.status !== 'active') return 'not_started';
    if (now > end) return 'expired';
    if (end.getTime() - now.getTime() <= 24 * 3600 * 1000) return 'expiring_soon';
    return 'active';
}

function fmt(dt: string): string {
    return new Date(dt).toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
}

export default function Index({ auth, permits, filters, sites, departments, types, statuses, riskLevels }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [departmentId, setDepartmentId] = useState(filters.department_id || '');
    const [type, setType] = useState(filters.type || '');
    const [status, setStatus] = useState(filters.status || '');
    const [riskLevel, setRiskLevel] = useState(filters.risk_level || '');
    const [validity, setValidity] = useState(filters.validity_status || '');

    const canCreate = permissions.has('permit.work.create');
    const canUpdate = permissions.has('permit.work.update');
    const canExport = permissions.has('permit.work.export');

    function apply() {
        router.get(route('permit.work.index'), {
            search: search || undefined,
            site_id: siteId || undefined,
            department_id: departmentId || undefined,
            type: type || undefined,
            status: status || undefined,
            risk_level: riskLevel || undefined,
            validity_status: validity || undefined,
        }, { preserveState: true, preserveScroll: true });
    }

    function reset() {
        setSearch(''); setSiteId(''); setDepartmentId(''); setType(''); setStatus(''); setRiskLevel(''); setValidity('');
        router.get(route('permit.work.index'));
    }

    const typeEntries = Object.entries(types);
    const statusEntries = Object.entries(statuses);
    const riskEntries = Object.entries(riskLevels);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Izin Kerja</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Manajemen Izin Kerja</h2>
                    </div>
                    <div className="flex gap-2">
                        {canExport && (
                            <SecondaryButton size="sm" href={route('permit.work.export') + '?' + new URLSearchParams({ search, site_id: siteId, department_id: departmentId, type, status, risk_level: riskLevel, validity_status: validity } as Record<string, string>).toString()}>Ekspor CSV</SecondaryButton>
                        )}
                        {canCreate && (
                            <PrimaryButton size="sm" href={route('permit.work.create')}>Buat Izin Kerja</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Izin Kerja" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, siteId, departmentId, type, status, riskLevel, validity].filter(v => v !== '').length}>
                        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                            <div className="lg:col-span-2">
                                <input type="text" placeholder="Cari nomor, judul, lokasi kerja..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && apply()} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Site: Semua</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Jenis: Semua</option>
                                {typeEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Status: Semua</option>
                                {statusEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={riskLevel} onChange={(e) => setRiskLevel(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Risk Level: Semua</option>
                                {riskEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <select value={validity} onChange={(e) => setValidity(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Validitas: Semua</option>
                                <option value="active">Aktif</option>
                                <option value="expiring_soon">Akan Berakhir</option>
                                <option value="expired">Kedaluwarsa</option>
                                <option value="not_started">Belum Aktif</option>
                            </select>
                            <select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Department: Semua</option>
                                {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                            <div className="flex gap-2 md:col-span-2">
                                <PrimaryButton type="button" onClick={apply}>Terapkan</PrimaryButton>
                                <SecondaryButton type="button" onClick={reset}>Reset</SecondaryButton>
                            </div>
                        </div>
                        </div>
                        </FilterPanel>

                        <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Jenis</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Validitas</th>
                                <th className="px-4 py-3 text-center">Periode</th>
                                <th className="px-4 py-3 text-center">Durasi</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3">Contractor</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {permits.data.length === 0 ? (
                                <tr>
                                    <td colSpan={10} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada izin kerja"
                                            description="Kelola izin kerja untuk aktivitas berisiko tinggi seperti hot work, confined space, dan working at height"
                                            action={canCreate ? <PrimaryButton href={route('permit.work.create')}>Buat Izin Kerja</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : permits.data.map((p) => (
                                <tr key={p.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                        <Link href={route('permit.work.show', p.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{p.permit_number}</Link>
                                    </td>
                                    <td className="max-w-xs truncate px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{p.title}</td>
                                    <td className="px-4 py-3"><PermitTypeBadge type={p.type} /></td>
                                    <td className="px-4 py-3 text-center"><StatusBadge status={p.status} /></td>
                                    <td className="px-4 py-3 text-center"><ValidityBadge status={validityOf(p)} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{fmt(p.start_datetime)}<br />{fmt(p.end_datetime)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-800 dark:text-slate-100">{p.validity_hours} jam</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-800 dark:text-slate-100">{p.site?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-800 dark:text-slate-100">{p.contractor?.name ?? '—'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('permit.work.show', p.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">👁</Link>
                                        {canUpdate && p.status === 'draft' && (
                                            <>
                                                <Link href={route('permit.work.edit', p.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">✏</Link>
                                                <DeleteWithConfirm
                                                    routeName="permit.work.destroy"
                                                    id={p.id}
                                                    permission="permit.work.delete"
                                                    itemLabel={p.permit_number}
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

                    {permits.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            <button disabled={permits.current_page <= 1} onClick={() => router.get(route('permit.work.index'), { ...buildFilters(), page: permits.current_page - 1 } as any, { preserveState: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">‹ Sebelumnya</button>
                            <span className="text-sm text-gray-600 dark:text-gray-400">{permits.current_page} / {permits.last_page}</span>
                            <button disabled={permits.current_page >= permits.last_page} onClick={() => router.get(route('permit.work.index'), { ...buildFilters(), page: permits.current_page + 1 } as any, { preserveState: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">Berikutnya ›</button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );

    function buildFilters() {
        return {
            search: search || undefined,
            site_id: siteId || undefined,
            department_id: departmentId || undefined,
            type: type || undefined,
            status: status || undefined,
            risk_level: riskLevel || undefined,
            validity_status: validity || undefined,
        };
    }
}
