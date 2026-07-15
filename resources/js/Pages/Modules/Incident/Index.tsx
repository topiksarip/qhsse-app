import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
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

type Filters = {
    search?: string;
    status?: string;
    category?: string;
    per_page?: string;
};

const categoryLabels: Record<string, string> = {
    accident: 'Accident',
    incident: 'Incident',
    near_miss: 'Near Miss',
    unsafe_act: 'Unsafe Act',
    unsafe_condition: 'Unsafe Condition',
    environmental_spill: 'Env. Spill',
    security_breach: 'Security Breach',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    under_review: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};

const statusLabels: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_review: 'Under Review',
    closed: 'Closed',
    rejected: 'Rejected',
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

    function reset() {
        router.get(route('incident.reports.index'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Laporan Insiden</h2>}>
            <Head title="Laporan Insiden" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Laporan Insiden</h1>
                        <div className="flex gap-2">
                            {permissions.has('incident.reports.export') && (
                                <Link href={route('incident.reports.export')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    Export CSV
                                </Link>
                            )}
                            {permissions.has('incident.reports.create') && (
                                <Link href={route('incident.reports.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Buat Laporan
                                </Link>
                            )}
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-4">
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nomor atau judul..."
                                className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 md:col-span-2"
                            />
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                                <option value="under_review">Under Review</option>
                                <option value="closed">Closed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <select value={category} onChange={(e) => setCategory(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Semua Kategori</option>
                                {Object.entries(categoryLabels).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                            <div className="flex gap-2 md:col-span-4">
                                <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                                <button type="button" onClick={reset} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                            </div>
                        </form>
                    </div>

                    {/* Table */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Nomor</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Judul</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Kategori</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Severity</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Tanggal</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Reporter</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {items.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-12">
                                                <EmptyState
                                                    title="Belum ada laporan insiden"
                                                    description="Mulai dokumentasikan insiden, near miss, dan unsafe condition di tempat kerja"
                                                    action={
                                                        permissions.has('incident.reports.create') ? (
                                                            <Link
                                                                href={route('incident.reports.create')}
                                                                className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                            >
                                                                Buat Laporan
                                                            </Link>
                                                        ) : undefined
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    ) : (
                                        items.data.map((item) => (
                                            <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    <Link href={route('incident.reports.show', item.id)} className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                                        {item.incident_number}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.title}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    <span className="inline-flex rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">
                                                        {categoryLabels[item.category] ?? item.category}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    {item.severity && (
                                                        <span className="inline-flex rounded-full px-2 py-1 text-xs font-semibold" style={{ backgroundColor: `${item.severity.color}20`, color: item.severity.color }}>
                                                            {item.severity.name}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                                        {statusLabels[item.status] ?? item.status}
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                    {new Date(item.occurred_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                <DeleteWithConfirm
                                                    routeName="incident.reports.destroy"
                                                    id={item.id}
                                                    permission="incident.reports.delete"
                                                    itemLabel={item.incident_number}
                                                    asLink
                                                    className="text-red-600 hover:underline dark:text-red-400"
                                                >
                                                    🗑 Hapus
                                                </DeleteWithConfirm>
                                            </td>
                                                    {item.reporter?.name ?? '-'}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <Pagination links={items.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
