import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';

type Option = { id: number; name: string };
type TypeOption = { value: string; label: string };
type DocumentItem = {
    id: number;
    document_number: string;
    title: string | null;
    type: string | null;
    version: string | null;
    status: string;
    effective_date: string | null;
    review_date: string | null;
    is_confidential: boolean;
    department?: Option | null;
    owner?: Option | null;
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
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">Dokumen Terkontrol</h2>}>
            <Head title="Dokumen Terkontrol" />
            <div className="py-10"><div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section className="flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-900 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
                    <div><p className="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-200">Controlled Repository</p><h1 className="mt-2 text-2xl font-bold">Document Register</h1><p className="mt-1 text-sm text-slate-300">SOP, WI, JSA, HIRADC, MSDS, policy, form, dan manual dengan lifecycle terkontrol.</p></div>
                    <div className="flex gap-2">
                        {permissions.has('document.control.export') && <Link href={route('document.control.export')} className="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold hover:bg-white/10">Export CSV</Link>}
                        {permissions.has('document.control.create') && <Link href={route('document.control.create')} className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-900 hover:bg-indigo-50">Buat Dokumen</Link>}
                    </div>
                </section>

                <form onSubmit={submit} className="grid gap-3 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 md:grid-cols-5">
                    <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white md:col-span-2" />
                    <select value={type} onChange={(e) => setType(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"><option value="">Semua tipe</option>{documentTypes.map((item) => <option key={item.value} value={item.value}>{item.label}</option>)}</select>
                    <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"><option value="">Semua status</option>{Object.entries(statusLabel).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>
                    <select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"><option value="">Semua department</option>{departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
                    <div className="flex gap-2 md:col-span-5"><button className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Terapkan</button><button type="button" onClick={() => router.get(route('document.control.index'))} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Reset</button></div>
                </form>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800"><div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                        <thead className="bg-slate-50 dark:bg-gray-900"><tr>{['Nomor', 'Judul', 'Tipe / Versi', 'Status', 'Owner', 'Review'].map((label) => <th key={label} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</th>)}</tr></thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-gray-700">
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada dokumen terkontrol"
                                            description="Kelola SOP, WI, JSA, HIRADC, MSDS, policy dengan lifecycle terkontrol"
                                            action={
                                                permissions.has('document.control.create') ? (
                                                    <Link
                                                        href={route('document.control.create')}
                                                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                    >
                                                        Buat Dokumen
                                                    </Link>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-gray-700/60">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><Link href={route('document.control.show', item.id)} className="font-semibold text-indigo-600 hover:text-indigo-800">{item.document_number}</Link>{item.is_confidential && <span className="ml-2" title="Rahasia">🔒</span>}</td>
                                    <td className="max-w-md px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{item.title || 'Belum diberi judul'}<div className="mt-1 text-xs font-normal text-slate-400">{item.department?.name ?? 'Lintas department'}</div></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300"><span className="font-semibold uppercase">{item.type || '-'}</span> · v{item.version || '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[item.status] ?? 'bg-slate-100 text-slate-700'}`}>{statusLabel[item.status] ?? item.status}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.owner?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.review_date ? new Date(item.review_date).toLocaleDateString('id-ID') : '-'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div></div>
                <Pagination links={items.links} />
            </div></div>
        </AuthenticatedLayout>
    );
}
