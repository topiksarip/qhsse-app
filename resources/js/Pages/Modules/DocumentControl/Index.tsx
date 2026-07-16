import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import FilterPanel from '@/Components/UI/FilterPanel';

type Option = { id: number; name: string };
type TypeOption = { value: string; label: string };
type DocumentItem = {
    id: number; document_number: string; title: string | null; type: string | null; version: string | null;
    status: string; effective_date: string | null; review_date: string | null; is_confidential: boolean;
    department?: Option | null; owner?: Option | null;
};
type Filters = { search?: string; type?: string; status?: string; department_id?: number | string };

const statusStyle: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700', review: 'bg-blue-100 text-blue-700', approved: 'bg-amber-100 text-amber-800',
    effective: 'bg-emerald-100 text-emerald-700', obsolete: 'bg-red-100 text-red-700', rejected: 'bg-rose-100 text-rose-700',
};
const statusLabel: Record<string, string> = { draft: 'Draft', review: 'Review', approved: 'Approved', effective: 'Effective', obsolete: 'Obsolete', rejected: 'Rejected' };

export default function Index({ items, filters, departments, documentTypes, auth }: PageProps<{
    items: Paginated<DocumentItem>; filters: Filters; departments: Option[]; documentTypes: TypeOption[];
}>) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [departmentId, setDepartmentId] = useState(String(filters.department_id ?? ''));

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('document.control.index'), { search, type, status, department_id: departmentId }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Dokumen</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Dokumen Terkontrol</h2>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('document.control.export') && (
                            <SecondaryButton size="sm" href={route('document.control.export')}>Export CSV</SecondaryButton>
                        )}
                        {permissions.has('document.control.create') && (
                            <PrimaryButton size="sm" href={route('document.control.create')}>Buat Dokumen</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Dokumen Terkontrol" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, type, status, departmentId].filter(v => v !== '').length}>
                        <form onSubmit={submit} className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-5">
                        <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                        <select value={type} onChange={(e) => setType(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Semua tipe</option>
                            {documentTypes.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}
                        </select>
                        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Semua status</option>
                            {Object.entries(statusLabel).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                        <select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Semua department</option>
                            {departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                        </select>
                        <div className="flex gap-2 md:col-span-5">
                            <PrimaryButton type="submit">Terapkan</PrimaryButton>
                            <SecondaryButton type="button" onClick={() => router.get(route('document.control.index'))}>Reset</SecondaryButton>
                        </div>
                        </form>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Tipe / Versi</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Owner</th>
                                <th className="px-4 py-3">Review</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada dokumen terkontrol"
                                            description="Kelola SOP, WI, JSA, HIRADC, MSDS, policy dengan lifecycle terkontrol"
                                            action={permissions.has('document.control.create') ? <PrimaryButton href={route('document.control.create')}>Buat Dokumen</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        <Link href={route('document.control.show', item.id)} className="font-semibold text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{item.document_number}</Link>
                                        {item.is_confidential && <span className="ml-2" title="Rahasia">🔒</span>}
                                    </td>
                                    <td className="max-w-md px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{item.title || 'Belum diberi judul'}<div className="mt-1 text-xs font-normal text-slate-400">{item.department?.name ?? 'Lintas department'}</div></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><span className="font-semibold uppercase">{item.type || '-'}</span> · v{item.version || '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[item.status] ?? 'bg-slate-100 text-slate-700'}`}>{statusLabel[item.status] ?? item.status}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.owner?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.review_date ? new Date(item.review_date).toLocaleDateString('id-ID') : '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <DeleteWithConfirm routeName="document.control.destroy" id={item.id} permission="document.control.delete" itemLabel={item.document_number} asLink className="text-red-600 hover:underline dark:text-red-400">🗑 Hapus</DeleteWithConfirm>
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
