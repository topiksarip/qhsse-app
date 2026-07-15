import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { Paginated } from '@/types/core';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type InspectionItem = {
    id: number; inspection_number: string; status: string; overall_result: string;
    scheduled_at: string; executed_at: string | null;
    template?: { name: string } | null; site?: { name: string } | null; inspector?: { name: string } | null;
};

const statusColors: Record<string, string> = { pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200', completed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' };
const resultColors: Record<string, string> = { pass: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200', fail: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200', pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' };

export default function Index({ items, filters, auth }: PageProps<{ items: Paginated<InspectionItem>; filters: Record<string, string> }>) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    function submit(e: FormEvent) { e.preventDefault(); router.get(route('inspection.checklists.index'), { search, status }, { preserveState: true, replace: true }); }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Inspeksi</h2>}>
            <Head title="Inspeksi" />
            <div className="py-12"><div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Inspeksi</h1>
                    <div className="flex gap-2">
                        {permissions.has('inspection.checklists.export') && <Link href={route('inspection.checklists.export')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Export CSV</Link>}
                        {permissions.has('inspection.checklists.create') && <Link href={route('inspection.checklists.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buat Inspeksi</Link>}
                    </div>
                </div>
                <div className="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <form onSubmit={submit} className="flex gap-3">
                        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor..." className="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">Semua Status</option><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option>
                        </select>
                        <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                    </form>
                </div>
                <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div className="overflow-x-auto"><table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-900"><tr>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Nomor</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Template</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Site</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Inspector</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Result</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Jadwal</th>
                        </tr></thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada inspeksi"
                                            description="Mulai dengan membuat inspeksi pertama Anda"
                                            action={
                                                permissions.has('inspection.checklists.create') ? (
                                                    <Link
                                                        href={route('inspection.checklists.create')}
                                                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                    >
                                                        Buat Inspeksi
                                                    </Link>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) :
                                items.data.map((item) => (
                                    <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td className="px-4 py-3 text-sm"><Link href={route('inspection.checklists.show', item.id)} className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{item.inspection_number}</Link></td>
                                        <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.template?.name ?? '-'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.site?.name ?? '-'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.inspector?.name ?? '-'}</td>
                                        <td className="px-4 py-3 text-sm"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? ''}`}>{item.status}</span></td>
                                        <td className="px-4 py-3 text-sm"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${resultColors[item.overall_result] ?? ''}`}>{item.overall_result}</span></td>
                                            <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                                <DeleteWithConfirm
                                                    routeName="inspection.checklists.destroy"
                                                    id={item.id}
                                                    permission="inspection.checklists.delete"
                                                    itemLabel={item.inspection_number}
                                                    asLink
                                                    className="text-red-600 hover:underline dark:text-red-400"
                                                >
                                                    🗑 Hapus
                                                </DeleteWithConfirm>
                                            </td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{new Date(item.scheduled_at).toLocaleDateString('id-ID')}</td>
                                    </tr>
                                ))}
                        </tbody>
                    </table></div>
                </div>
                <Pagination links={items.links} />
            </div></div>
        </AuthenticatedLayout>
    );
}
