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
    function isOverdue(item: CapaItem) { return item.due_date && new Date(item.due_date) < new Date() && !['closed', 'rejected'].includes(item.status); }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Tindakan Perbaikan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">CAPA / Action Tracking</h2>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('capa.actions.export') && (
                            <SecondaryButton size="sm" href={route('capa.actions.export')}>Export CSV</SecondaryButton>
                        )}
                        {permissions.has('capa.actions.create') && (
                            <PrimaryButton size="sm" href={route('capa.actions.create')}>Buat Action</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="CAPA / Action Tracking" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <form onSubmit={submit} className="grid gap-3 md:grid-cols-3">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari..." className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                            <Select value={status} onChange={(e) => setStatus(e.target.value)}>
                                <option value="">Semua Status</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="waiting_verification">Waiting Verification</option>
                                <option value="closed">Closed</option>
                                <option value="rejected">Rejected</option>
                            </Select>
                            <div className="flex gap-2 md:col-span-3">
                                <PrimaryButton type="submit">Filter</PrimaryButton>
                                <SecondaryButton onClick={() => router.get(route('capa.actions.index'))}>Reset</SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">PIC</th>
                                <th className="px-4 py-3">Due Date</th>
                                <th className="px-4 py-3">Priority</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada CAPA action"
                                            description="Mulai dengan membuat corrective/preventive action pertama Anda"
                                            action={
                                                permissions.has('capa.actions.create') ? (
                                                    <PrimaryButton href={route('capa.actions.create')}>Buat Action</PrimaryButton>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : (
                                items.data.map((item) => (
                                    <tr key={item.id} className={isOverdue(item) ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-slate-50 dark:hover:bg-gray-800'}>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <Link href={route('capa.actions.show', item.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{item.action_number}</Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{item.title}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[item.status] ?? ''}`}>{statusLabels[item.status] ?? item.status}</span>
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.assignedTo?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            {item.due_date ? (
                                                <span className={isOverdue(item) ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'}>
                                                    {new Date(item.due_date).toLocaleDateString('id-ID')}
                                                </span>
                                            ) : '-'}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm">
                                            {item.priority && (
                                                <span className="inline-flex rounded-full px-2 py-1 text-xs font-semibold" style={{ backgroundColor: `${item.priority.color}20`, color: item.priority.color }}>{item.priority.name}</span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                            <DeleteWithConfirm
                                                routeName="capa.actions.destroy"
                                                id={item.id}
                                                permission="capa.actions.delete"
                                                itemLabel={item.action_number}
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

function Select({ value, onChange, children }: { value: string; onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void; children: React.ReactNode }) {
    return (
        <select value={value} onChange={onChange} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
            {children}
        </select>
    );
}
