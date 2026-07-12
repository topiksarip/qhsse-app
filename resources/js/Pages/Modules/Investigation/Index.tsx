import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import { Paginated } from '@/types/core';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type InvestigationItem = {
    id: number;
    investigation_number: string;
    title: string;
    status: string;
    created_at: string;
    incident?: { incident_number: string; title: string } | null;
    investigator?: { name: string } | null;
};

type Filters = { search?: string; status?: string };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};
const statusLabels: Record<string, string> = { draft: 'Draft', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' };

export default function Index({ items, filters, auth }: PageProps<{ items: Paginated<InvestigationItem>; filters: Filters }>) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function submit(e: FormEvent) {
        e.preventDefault();
        router.get(route('investigation.reports.index'), { search, status }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Investigasi & RCA</h2>}>
            <Head title="Investigasi & RCA" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Investigasi & RCA</h1>
                        <div className="flex gap-2">
                            {permissions.has('investigation.reports.export') && (
                                <Link href={route('investigation.reports.export')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Export CSV</Link>
                            )}
                            {permissions.has('investigation.reports.create') && (
                                <Link href={route('investigation.reports.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buat Investigasi</Link>
                            )}
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-3">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 md:col-span-2" />
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div className="flex gap-2 md:col-span-3">
                                <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                                <button type="button" onClick={() => router.get(route('investigation.reports.index'))} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                            </div>
                        </form>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Nomor</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Judul</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Insiden</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Investigator</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Dibuat</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {items.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-12">
                                                <EmptyState
                                                    title="Belum ada investigasi"
                                                    description="Mulai dengan membuat laporan investigasi pertama Anda"
                                                    action={
                                                        permissions.has('investigation.reports.create') ? (
                                                            <Link
                                                                href={route('investigation.reports.create')}
                                                                className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                            >
                                                                Buat Investigasi
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
                                                    <Link href={route('investigation.reports.show', item.id)} className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{item.investigation_number}</Link>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.title}</td>
                                                <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.incident?.incident_number ?? '-'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? ''}`}>{statusLabels[item.status] ?? item.status}</span>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.investigator?.name ?? '-'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
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
