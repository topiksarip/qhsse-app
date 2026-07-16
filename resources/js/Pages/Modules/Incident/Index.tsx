import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import FilterPanel from '@/Components/UI/FilterPanel';
import { Paginated } from '@/types/core';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type IncidentItem = {
    id: number;
    incident_number: string;
    title: string;
    category: string;
    status: string;
    occurred_at: string;
    site?: { name: string } | null;
    severity?: { name: string; color: string; level: number } | null;
    priority?: { name: string; color: string; level: number } | null;
    reporter?: { name: string } | null;
};

type Filters = { search?: string; status?: string; category?: string; per_page?: string };

const categoryLabels: Record<string, string> = {
    accident: 'Accident', incident: 'Incident', near_miss: 'Near Miss', unsafe_act: 'Unsafe Act',
    unsafe_condition: 'Unsafe Condition', environmental_spill: 'Env. Spill', security_breach: 'Security Breach',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    under_review: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft', submitted: 'Submitted', under_review: 'Under Review', closed: 'Closed', rejected: 'Rejected',
};

export default function Index({ items, filters, auth }: PageProps<{ items: Paginated<IncidentItem>; filters: Filters }>) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    function submit(e: FormEvent) {
        e.preventDefault();
        router.get(route('incident.reports.index'), { search, status, category }, { preserveState: true, replace: true });
    }
    function reset() { router.get(route('incident.reports.index')); }

    const activeCount = [search !== '', status !== '', category !== ''].filter(Boolean).length;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Insiden</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Laporan Insiden</h2>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('incident.reports.export') && (
                            <SecondaryButton size="sm" href={route('incident.reports.export')}>Export CSV</SecondaryButton>
                        )}
                        {permissions.has('incident.reports.create') && (
                            <PrimaryButton size="sm" href={route('incident.reports.create')}>Buat Laporan</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Laporan Insiden" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={activeCount}>
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-4">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                {Object.entries(statusLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <select value={category} onChange={(e) => setCategory(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Kategori</option>
                                {Object.entries(categoryLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                            </select>
                            <div className="flex gap-2 md:col-span-4">
                                <PrimaryButton type="submit">Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={reset}>Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Kategori</th>
                                <th className="px-4 py-3">Severity</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Tanggal</th>
                                <th className="px-4 py-3">Reporter</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada laporan insiden"
                                            description="Mulai dokumentasikan insiden, near miss, dan unsafe condition di tempat kerja"
                                            action={permissions.has('incident.reports.create') ? <PrimaryButton href={route('incident.reports.create')}>Buat Laporan</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        <Link href={route('incident.reports.show', item.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{item.incident_number}</Link>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-slate-800 dark:text-slate-200">{item.title}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        <span className="inline-flex rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">{categoryLabels[item.category] ?? item.category}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        {item.severity && (
                                            <span className="inline-flex rounded-full px-2 py-1 text-xs font-semibold" style={{ backgroundColor: `${item.severity.color}20`, color: item.severity.color }}>{item.severity.name}</span>
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? 'bg-gray-100 text-gray-800'}`}>{statusLabels[item.status] ?? item.status}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{new Date(item.occurred_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.reporter?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <DeleteWithConfirm routeName="incident.reports.destroy" id={item.id} permission="incident.reports.delete" itemLabel={item.incident_number} asLink className="text-red-600 hover:underline dark:text-red-400">🗑 Hapus</DeleteWithConfirm>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>
                    <Pagination links={items.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
