import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, RiskRegister, Site, Department, Area, Severity, RiskMatrixLevel } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import TypeBadge from '@/Components/Risk/TypeBadge';
import StatusBadge from '@/Components/Risk/StatusBadge';
import RiskLevelBadge from '@/Components/Risk/RiskLevelBadge';

interface IndexProps extends PageProps {
    items: PaginatedData<RiskRegister>;
    filters: Record<string, string | null>;
    sites: Site[];
    areas: Area[];
    departments: Department[];
    severities: Severity[];
    riskLevels: RiskMatrixLevel[];
    users: { id: number; name: string }[];
}

const typeOptions: { value: string; label: string }[] = [
    { value: 'hazard_identification', label: 'Hazard ID' }, { value: 'jsa', label: 'JSA' },
    { value: 'hiradc', label: 'HIRADC' }, { value: 'risk_assessment', label: 'Risk Assessment' },
];

const statusOptions: { value: string; label: string }[] = [
    { value: 'identified', label: 'Teridentifikasi' }, { value: 'assessed', label: 'Dinilai' },
    { value: 'controls_needed', label: 'Perlu Kontrol' }, { value: 'controls_in_place', label: 'Kontrol Terpasang' },
    { value: 'monitored', label: 'Dipantau' }, { value: 'obsolete', label: 'Tidak Berlaku' },
];

export default function Index({ auth, items, filters, sites, areas, departments, severities, riskLevels, users }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canCreate = permissions.has('risk.registers.create');
    const canExport = permissions.has('risk.registers.export');

    const [search, setSearch] = useState(filters.search ?? '');
    const [siteId, setSiteId] = useState(filters.site_id ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [riskLevelId, setRiskLevelId] = useState(filters.risk_level_id ?? '');

    function buildParams(extra: Record<string, unknown> = {}): Record<string, string> {
        const raw: Record<string, unknown> = {
            search: search || undefined, site_id: siteId || undefined, type: type || undefined,
            status: status || undefined, risk_level_id: riskLevelId || undefined, ...extra,
        };
        return Object.fromEntries(
            Object.entries(raw).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]),
        ) as Record<string, string>;
    }

    function apply() { router.get(route('risk.registers.index'), buildParams(), { preserveState: true, replace: true }); }
    function reset() { setSearch(''); setSiteId(''); setType(''); setStatus(''); setRiskLevelId(''); router.get(route('risk.registers.index'), {}, { preserveState: true, replace: true }); }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Risiko</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Risk Register</h2>
                    </div>
                    <div className="flex gap-2">
                        {canExport && (
                            <SecondaryButton size="sm" href={route('risk.registers.export') + '?' + new URLSearchParams(buildParams()).toString()}>Export CSV</SecondaryButton>
                        )}
                        {canCreate && (
                            <PrimaryButton size="sm" href={route('risk.registers.create')}>Buat Risk Register</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Risk Register" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
                            <div className="lg:col-span-2">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && apply()} placeholder="Cari nomor, judul, aktivitas..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Tipe</option>
                                {typeOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                            </select>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                {statusOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                            </select>
                            <select value={riskLevelId} onChange={(e) => setRiskLevelId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Risk Level</option>
                                {riskLevels.map((r) => <option key={r.id} value={r.id}>{r.level} ({r.score})</option>)}
                            </select>
                            <div className="flex items-end gap-2">
                                <PrimaryButton type="button" onClick={apply}>Terapkan</PrimaryButton>
                                <SecondaryButton type="button" onClick={reset}>Reset</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Tipe</th>
                                <th className="px-4 py-3 text-center">Risk Level</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada risk register"
                                            description="Identifikasi dan kelola risiko dengan HIRADC, JSA, dan risk assessment"
                                            action={canCreate ? <PrimaryButton href={route('risk.registers.create')}>Buat Risk Register</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : items.data.map((r) => (
                                <tr key={r.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                        <Link href={route('risk.registers.show', r.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{r.register_number}</Link>
                                    </td>
                                    <td className="max-w-xs px-4 py-3 text-sm">
                                        <Link href={route('risk.registers.show', r.id)} className="font-medium text-slate-800 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-400">{r.title}</Link>
                                    </td>
                                    <td className="px-4 py-3"><TypeBadge type={r.type} /></td>
                                    <td className="px-4 py-3 text-center"><RiskLevelBadge level={r.riskLevel} /></td>
                                    <td className="px-4 py-3 text-center"><StatusBadge status={r.status} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('risk.registers.show', r.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">👁</Link>
                                        <DeleteWithConfirm
                                            routeName="risk.registers.destroy"
                                            id={r.id}
                                            permission="risk.registers.delete"
                                            itemLabel={r.register_number}
                                            asLink
                                            className="ml-2 text-red-600 hover:underline dark:text-red-400"
                                        >
                                            🗑
                                        </DeleteWithConfirm>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {items.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            <button disabled={items.current_page <= 1} onClick={() => router.get(route('risk.registers.index'), buildParams({ page: items.current_page - 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">‹ Sebelumnya</button>
                            <span className="text-sm text-gray-600 dark:text-gray-400">{items.current_page} / {items.last_page}</span>
                            <button disabled={items.current_page >= items.last_page} onClick={() => router.get(route('risk.registers.index'), buildParams({ page: items.current_page + 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">Berikutnya ›</button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
