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
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Analisis Akar Masalah</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Investigasi & RCA</h2>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('investigation.reports.export') && (
                            <SecondaryButton size="sm" href={route('investigation.reports.export')}>Export CSV</SecondaryButton>
                        )}
                        {permissions.has('investigation.reports.create') && (
                            <PrimaryButton size="sm" href={route('investigation.reports.create')}>Buat Investigasi</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Investigasi & RCA" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-3">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div className="flex gap-2 md:col-span-3">
                                <PrimaryButton type="submit">Filter</PrimaryButton>
                                <SecondaryButton onClick={() => router.get(route('investigation.reports.index'))}>Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Insiden</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Investigator</th>
                                <th className="px-4 py-3">Dibuat</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada investigasi"
                                            description="Mulai dengan membuat laporan investigasi pertama Anda"
                                            action={
                                                permissions.has('investigation.reports.create') ? (
                                                    <PrimaryButton href={route('investigation.reports.create')}>Buat Investigasi</PrimaryButton>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : (
                                items.data.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <Link href={route('investigation.reports.show', item.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{item.investigation_number}</Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.title}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.incident?.incident_number ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? ''}`}>{statusLabels[item.status] ?? item.status}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.investigator?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                            <DeleteWithConfirm
                                                routeName="investigation.reports.destroy"
                                                id={item.id}
                                                permission="investigation.reports.delete"
                                                itemLabel={item.investigation_number}
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
