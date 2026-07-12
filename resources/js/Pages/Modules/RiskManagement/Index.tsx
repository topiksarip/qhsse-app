import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, RiskRegister, Site, Department, Area, Severity, RiskMatrixLevel } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
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
    { value: 'hazard_identification', label: 'Hazard ID' },
    { value: 'jsa', label: 'JSA' },
    { value: 'hiradc', label: 'HIRADC' },
    { value: 'risk_assessment', label: 'Risk Assessment' },
];

const statusOptions: { value: string; label: string }[] = [
    { value: 'identified', label: 'Teridentifikasi' },
    { value: 'assessed', label: 'Dinilai' },
    { value: 'controls_needed', label: 'Perlu Kontrol' },
    { value: 'controls_in_place', label: 'Kontrol Terpasang' },
    { value: 'monitored', label: 'Dipantau' },
    { value: 'obsolete', label: 'Tidak Berlaku' },
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
            search: search || undefined,
            site_id: siteId || undefined,
            type: type || undefined,
            status: status || undefined,
            risk_level_id: riskLevelId || undefined,
            ...extra,
        };
        return Object.fromEntries(
            Object.entries(raw).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]),
        ) as Record<string, string>;
    }

    function apply() {
        router.get(route('risk.registers.index'), buildParams(), { preserveState: true, replace: true });
    }

    function reset() {
        setSearch(''); setSiteId(''); setType(''); setStatus(''); setRiskLevelId('');
        router.get(route('risk.registers.index'), {}, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Risk Register</h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Daftar risiko teridentifikasi dan terdaftar</p>
                    </div>
                    {canCreate && (
                        <Link href={route('risk.registers.create')} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            + Buat Risk Register
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Risk Register" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
                            <div className="lg:col-span-2">
                                <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && apply()} placeholder="Cari nomor, judul, aktivitas..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Tipe</option>
                                    {typeOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Status</option>
                                    {statusOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={riskLevelId} onChange={(e) => setRiskLevelId(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Semua Risk Level</option>
                                    {riskLevels.map((r) => <option key={r.id} value={r.id}>{r.level} ({r.score})</option>)}
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <button onClick={apply} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Terapkan</button>
                                <button onClick={reset} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Menampilkan {items.from ?? 0}–{items.to ?? 0} dari {items.total} risk register</p>
                        {canExport && (
                            <a href={route('risk.registers.export') + '?' + new URLSearchParams(buildParams()).toString()} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                ⬇ Export CSV
                            </a>
                        )}
                    </div>

                    {items.data.length === 0 ? (
                        <div className="rounded-lg bg-white p-12 shadow dark:bg-gray-800">
                            <EmptyState
                                title="Belum ada risk register"
                                description="Identifikasi dan kelola risiko dengan HIRADC, JSA, dan risk assessment"
                                action={
                                    canCreate ? (
                                        <Link
                                            href={route('risk.registers.create')}
                                            className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            Buat Risk Register
                                        </Link>
                                    ) : undefined
                                }
                            />
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr className="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                            <th className="px-4 py-3">Nomor</th>
                                            <th className="px-4 py-3">Judul</th>
                                            <th className="px-4 py-3">Tipe</th>
                                            <th className="px-4 py-3 text-center">Risk Level</th>
                                            <th className="px-4 py-3 text-center">Status</th>
                                            <th className="px-4 py-3 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {items.data.map((r) => (
                                            <tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">{r.register_number}</td>
                                                <td className="max-w-xs px-4 py-3 text-sm">
                                                    <Link href={route('risk.registers.show', r.id)} className="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{r.title}</Link>
                                                </td>
                                                <td className="px-4 py-3"><TypeBadge type={r.type} /></td>
                                                <td className="px-4 py-3 text-center"><RiskLevelBadge level={r.riskLevel} /></td>
                                                <td className="px-4 py-3 text-center"><StatusBadge status={r.status} /></td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                    <Link href={route('risk.registers.show', r.id)} className="text-indigo-600 hover:underline dark:text-indigo-400">👁</Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {items.last_page > 1 && (
                                <div className="mt-4 flex items-center justify-center gap-2">
                                    <button disabled={items.current_page <= 1} onClick={() => router.get(route('risk.registers.index'), buildParams({ page: items.current_page - 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-200 px-3 py-1 text-sm disabled:opacity-50 dark:bg-gray-700">‹ Sebelumnya</button>
                                    <span className="text-sm text-gray-600 dark:text-gray-400">{items.current_page} / {items.last_page}</span>
                                    <button disabled={items.current_page >= items.last_page} onClick={() => router.get(route('risk.registers.index'), buildParams({ page: items.current_page + 1 }), { preserveState: true, replace: true })} className="rounded-md bg-gray-200 px-3 py-1 text-sm disabled:opacity-50 dark:bg-gray-700">Berikutnya ›</button>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
