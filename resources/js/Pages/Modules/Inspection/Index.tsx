import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
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
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Pemeriksaan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Inspeksi</h2>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('inspection.checklists.export') && (
                            <SecondaryButton size="sm" href={route('inspection.checklists.export')}>Export CSV</SecondaryButton>
                        )}
                        {permissions.has('inspection.checklists.create') && (
                            <PrimaryButton size="sm" href={route('inspection.checklists.create')}>Buat Inspeksi</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Inspeksi" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <form onSubmit={submit} className="flex flex-wrap gap-3">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor..." className="flex-1 rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                            <PrimaryButton type="submit">Filter</PrimaryButton>
                            <SecondaryButton onClick={() => router.get(route('inspection.checklists.index'))}>Reset</SecondaryButton>
                        </form>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Template</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3">Inspector</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Result</th>
                                <th className="px-4 py-3">Jadwal</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada inspeksi"
                                            description="Mulai dengan membuat inspeksi pertama Anda"
                                            action={
                                                permissions.has('inspection.checklists.create') ? (
                                                    <PrimaryButton href={route('inspection.checklists.create')}>Buat Inspeksi</PrimaryButton>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : (
                                items.data.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                        <td className="px-4 py-3 text-sm">
                                            <Link href={route('inspection.checklists.show', item.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{item.inspection_number}</Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.template?.name ?? '-'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.site?.name ?? '-'}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.inspector?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? ''}`}>{item.status}</span></td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${resultColors[item.overall_result] ?? ''}`}>{item.overall_result}</span></td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{new Date(item.scheduled_at).toLocaleDateString('id-ID')}</td>
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
                                    </tr>
                                ))
                            )}
                        </TableBody>
                    </TableWrapper>
                    <Pagination links={items.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
