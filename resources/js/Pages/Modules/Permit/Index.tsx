import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, Permit, Site, Department } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PermitTypeBadge from '@/Components/Permit/PermitTypeBadge';
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
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [departmentId, setDepartmentId] = useState(filters.department_id || '');
    const [type, setType] = useState(filters.type || '');
    const [status, setStatus] = useState(filters.status || '');
    const [riskLevel, setRiskLevel] = useState(filters.risk_level || '');
    const [validity, setValidity] = useState(filters.validity_status || '');

    const permissions = new Set(auth.permissions ?? []);
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
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Izin Kerja</h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola izin kerja untuk aktivitas berisiko tinggi</p>
                    </div>
                    {canCreate && (
                        <Link href={route('permit.work.create')} className="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            + Buat Izin Kerja
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Izin Kerja" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Filter Bar */}
                    <div className="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div className="lg:col-span-2">
                                <input
                                    type="text"
                                    placeholder="🔍 Cari nomor, judul, lokasi kerja..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && apply()}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Site: Semua</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Jenis: Semua</option>
                                    {typeEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Status: Semua</option>
                                    {statusEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={riskLevel} onChange={(e) => setRiskLevel(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Risk Level: Semua</option>
                                    {riskEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <select value={validity} onChange={(e) => setValidity(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Validitas: Semua</option>
                                    <option value="active">Aktif</option>
                                    <option value="expiring_soon">Akan Berakhir</option>
                                    <option value="expired">Kedaluwarsa</option>
                                    <option value="not_started">Belum Aktif</option>
                                </select>
                            </div>
                            <div>
                                <select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Department: Semua</option>
                                    {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <button onClick={apply} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                            <button onClick={reset} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">Reset</button>
                            {canExport && (
                                <a href={route('permit.work.export') + '?' + new URLSearchParams({ search, site_id: siteId, department_id: departmentId, type, status, risk_level: riskLevel, validity_status: validity } as Record<string, string>).toString()} className="ml-auto inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">⬇ Export CSV</a>
                            )}
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div className="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Menampilkan {permits.from || 0}–{permits.to || 0} dari {permits.total} izin kerja
                            </p>
                        </div>
                        {permits.data.length === 0 ? (
                            <div className="p-12">
                                <EmptyState
                                    title="Belum ada izin kerja"
                                    description="Kelola izin kerja untuk aktivitas berisiko tinggi seperti hot work, confined space, dan working at height"
                                    action={
                                        canCreate ? (
                                            <Link
                                                href={route('permit.work.create')}
                                                className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                            >
                                                Buat Izin Kerja
                                            </Link>
                                        ) : undefined
                                    }
                                />
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nomor</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Judul</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jenis</th>
                                            <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                            <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Validitas</th>
                                            <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Periode</th>
                                            <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Durasi</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Site</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Contractor</th>
                                            <th className="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                        {permits.data.map((p) => (
                                            <tr key={p.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="whitespace-nowrap px-4 py-3"><Link href={route('permit.work.show', p.id)} className="font-mono text-sm text-indigo-600 hover:underline dark:text-indigo-400">{p.permit_number}</Link></td>
                                                <td className="max-w-xs truncate px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{p.title}</td>
                                                <td className="px-4 py-3"><PermitTypeBadge type={p.type} /></td>
                                                <td className="px-4 py-3 text-center"><StatusBadge status={p.status} /></td>
                                                <td className="px-4 py-3 text-center"><ValidityBadge status={validityOf(p)} /></td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">{fmt(p.start_datetime)}<br />{fmt(p.end_datetime)}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100">{p.validity_hours} jam</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{p.site?.name ?? '-'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{p.contractor?.name ?? '—'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <Link href={route('permit.work.show', p.id)} className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">👁 Lihat</Link>
                                                        {canUpdate && p.status === 'draft' && (
                                                            <><Link href={route('permit.work.edit', p.id)} className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">✏ Edit</Link>                                            <DeleteWithConfirm
                                                routeName="permit.work.destroy"
                                                id={p.id}
                                                permission="permit.work.delete"
                                                itemLabel={p.permit_number}
                                                asLink
                                                className="ml-2 text-red-600 hover:underline dark:text-red-400"
                                            >
                                                🗑
                                            </DeleteWithConfirm></>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        {permits.last_page > 1 && (
                            <div className="flex items-center justify-center gap-2 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                                {permits.current_page > 1 && <Link href={route('permit.work.index', { ...filters, page: permits.current_page - 1 })} className="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">‹ Sebelumnya</Link>}
                                {Array.from({ length: permits.last_page }, (_, i) => i + 1).map((page) => (
                                    <Link key={page} href={route('permit.work.index', { ...filters, page })} className={`rounded border px-3 py-1 text-sm ${page === permits.current_page ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'}`}>{page}</Link>
                                ))}
                                {permits.current_page < permits.last_page && <Link href={route('permit.work.index', { ...filters, page: permits.current_page + 1 })} className="rounded border border-gray-300 px-3 py-1 text-sm dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Berikutnya ›</Link>}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
