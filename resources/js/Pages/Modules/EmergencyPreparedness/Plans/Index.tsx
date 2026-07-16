import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyPlan, Site, PageProps, PaginatedData } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import FilterPanel from '@/Components/UI/FilterPanel';

interface PlanIndexProps extends PageProps {
    plans: PaginatedData<EmergencyPlan>;
    filters: { search?: string; type?: string; site_id?: number };
    sites: Site[];
    can: { create: boolean; export: boolean; delete: boolean };
}

const planTypeColors: Record<string, string> = {
    fire: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    medical: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    spill: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    evacuation: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    natural_disaster: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    security: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    other: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};
const planTypeLabels: Record<string, string> = {
    fire: 'Kebakaran', medical: 'Medis', spill: 'Tumpahan', evacuation: 'Evakuasi',
    natural_disaster: 'Bencana Alam', security: 'Keamanan', other: 'Lainnya',
};

export default function Index({ auth, plans, filters, sites, can }: PlanIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');

    const handleFilter = () => router.get(route('emergency.plans.index'), { search: search || undefined, type: type || undefined, site_id: siteId || undefined }, { preserveState: true, preserveScroll: true });
    const handleReset = () => { setSearch(''); setType(''); setSiteId(''); router.get(route('emergency.plans.index')); };
    const handleExport = () => { window.location.href = route('emergency.plans.export', filters); };
    const typeBadge = (t: string) => {
        const c = planTypeColors[t] || planTypeColors.other;
        return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c}`}>{planTypeLabels[t] || t}</span>;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-red-600 dark:text-red-400">Darurat</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Rencana Darurat</h2>
                    </div>
                    {can.create && <PrimaryButton size="sm" href={route('emergency.plans.create')} className="bg-red-600 hover:bg-red-700 focus:ring-red-500">+ Buat Rencana</PrimaryButton>}
                </div>
            }
        >
            <Head title="Rencana Darurat" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, type, siteId].filter(v => v !== '').length}>
                        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <input type="text" placeholder="🔍 Cari nomor, nama..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && handleFilter()} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <select value={type} onChange={(e) => setType(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Tipe</option>
                                {Object.entries(planTypeLabels).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                            </select>
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="button" onClick={handleFilter}>Filter</PrimaryButton>
                            <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                        </div>
                        </div>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Tipe</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3">Kontak</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {plans.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12">
                                        <EmptyState title="Belum ada rencana darurat" description="Kelola rencana kesiapsiagaan: kebakaran, medis, tumpahan, evakuasi, bencana alam" action={can.create ? <PrimaryButton href={route('emergency.plans.create')} className="bg-red-600 hover:bg-red-700 focus:ring-red-500">+ Buat Rencana Pertama</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : plans.data.map((plan) => (
                                <tr key={plan.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                        <Link href={route('emergency.plans.show', plan.id)} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{plan.plan_number}</Link>
                                    </td>
                                    <td className="max-w-xs truncate px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{plan.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">{typeBadge(plan.type)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{plan.site?.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{plan.contact_person?.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('emergency.plans.show', plan.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Lihat</Link>
                                        {can.create && <Link href={route('emergency.plans.edit', plan.id)} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>}
                                        {can.delete && (
                                            <DeleteWithConfirm
                                                routeName="emergency.plans.destroy"
                                                id={plan.id}
                                                permission="emergency.plans.delete"
                                                itemLabel={plan.plan_number}
                                                redirectTo="emergency.plans.index"
                                                asLink
                                            >
                                                Delete
                                            </DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Menampilkan {plans.from} – {plans.to} dari {plans.total} rencana</span>
                        {can.export && <SecondaryButton size="sm" type="button" onClick={handleExport}>⬇ Export CSV</SecondaryButton>}
                    </div>

                    {plans.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {plans.current_page > 1 && <Link href={route('emergency.plans.index', { ...filters, page: plans.current_page - 1 })} className="rounded-md border border-slate-300 px-3 py-1 text-sm text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">‹ Sebelumnya</Link>}
                            {[...Array(plans.last_page)].map((_, i) => (
                                <Link key={i + 1} href={route('emergency.plans.index', { ...filters, page: i + 1 })} className={`rounded-md border px-3 py-1 text-sm ${plans.current_page === i + 1 ? 'border-red-600 bg-red-600 text-white' : 'border-slate-300 text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800'}`}>{i + 1}</Link>
                            ))}
                            {plans.current_page < plans.last_page && <Link href={route('emergency.plans.index', { ...filters, page: plans.current_page + 1 })} className="rounded-md border border-slate-300 px-3 py-1 text-sm text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Berikutnya ›</Link>}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
