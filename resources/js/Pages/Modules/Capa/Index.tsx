import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import { Paginated } from '@/types/core';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type CapaItem = {
    id: number; action_number: string; title: string; status: string; source_module: string | null;
    due_date: string | null; created_at: string;
    site?: { name: string } | null; assigned_to_user?: { name: string } | null;
    assignedTo?: { name: string } | null; priority?: { name: string; color: string } | null;
};

type Filters = { search?: string; status?: string };

const statusColors: Record<string, string> = {
    open: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    waiting_verification: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};
const statusLabels: Record<string, string> = { open: 'Open', in_progress: 'In Progress', waiting_verification: 'Waiting Verification', closed: 'Closed', rejected: 'Rejected' };

export default function Index({ items, filters, auth }: PageProps<{ items: Paginated<CapaItem>; filters: Filters }>) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function submit(e: FormEvent) { e.preventDefault(); router.get(route('capa.actions.index'), { search, status }, { preserveState: true, replace: true }); }
    function isOverdue(item: CapaItem) { return item.due_date && new Date(item.due_date) < new Date() && !['closed','rejected'].includes(item.status); }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">CAPA / Action Tracking</h2>}>
            <Head title="CAPA / Action Tracking" />
            <div className="py-12"><div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">CAPA / Action Tracking</h1>
                    <div className="flex gap-2">
                        {permissions.has('capa.actions.export') && <Link href={route('capa.actions.export')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Export CSV</Link>}
                        {permissions.has('capa.actions.create') && <Link href={route('capa.actions.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buat Action</Link>}
                    </div>
                </div>
                <div className="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <form onSubmit={submit} className="grid gap-3 md:grid-cols-3">
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari..." className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 md:col-span-2" />
                        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">Semua Status</option>
                            <option value="open">Open</option><option value="in_progress">In Progress</option>
                            <option value="waiting_verification">Waiting Verification</option>
                            <option value="closed">Closed</option><option value="rejected">Rejected</option>
                        </select>
                        <div className="flex gap-2 md:col-span-3">
                            <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                            <button type="button" onClick={() => router.get(route('capa.actions.index'))} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                        </div>
                    </form>
                </div>
                <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900"><tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Nomor</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Judul</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">PIC</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Due Date</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Priority</th>
                            </tr></thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {items.data.length === 0 ? (<tr><td colSpan={6} className="px-4 py-12 text-center text-gray-500 dark:text-gray-400">Belum ada CAPA action.</td></tr>) : (
                                    items.data.map((item) => (
                                        <tr key={item.id} className={isOverdue(item) ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700'}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm"><Link href={route('capa.actions.show', item.id)} className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{item.action_number}</Link></td>
                                            <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.title}</td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? ''}`}>{statusLabels[item.status] ?? item.status}</span></td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.assignedTo?.name ?? '-'}</td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm">{item.due_date ? <span className={isOverdue(item) ? 'font-semibold text-red-600' : 'text-gray-500 dark:text-gray-400'}>{new Date(item.due_date).toLocaleDateString('id-ID')}</span> : '-'}</td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm">{item.priority && <span className="inline-flex rounded-full px-2 py-1 text-xs font-semibold" style={{ backgroundColor: `${item.priority.color}20`, color: item.priority.color }}>{item.priority.name}</span>}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
                <Pagination links={items.links} />
            </div></div>
        </AuthenticatedLayout>
    );
}
