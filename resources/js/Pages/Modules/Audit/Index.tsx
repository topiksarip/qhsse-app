import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/UI/EmptyState';
import { PageProps } from '@/types';
import { Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Option = { id: number; name: string };
type AuditItem = {
    id: number;
    audit_number: string;
    title: string | null;
    audit_type: string | null;
    status: string;
    scheduled_date: string | null;
    lead_auditor?: Option | null;
    department?: Option | null;
    findings_count: number;
};
type Filters = {
    search?: string;
    status?: string;
    audit_type?: string;
    department_id?: number | string;
    date_from?: string;
    date_to?: string;
};

const statusStyle: Record<string, string> = {
    planned: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-800',
    report_ready: 'bg-purple-100 text-purple-700',
    closed: 'bg-emerald-100 text-emerald-700',
};
const statusLabel: Record<string, string> = {
    planned: 'Direncanakan',
    in_progress: 'Berlangsung',
    report_ready: 'Laporan Siap',
    closed: 'Ditutup',
};
const typeLabel: Record<string, string> = {
    internal: 'Internal',
    external: 'Eksternal',
    supplier: 'Pemasok',
    regulatory: 'Regulator',
};

export default function Index({ audits, filters, departments, auth }: PageProps<{
    audits: Paginated<AuditItem>; filters: Filters; departments: Option[];
}>) {
    const permissions = new Set(auth.permissions ?? []);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [type, setType] = useState(filters.audit_type ?? '');
    const [departmentId, setDepartmentId] = useState(String(filters.department_id ?? ''));
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('audits.index'), {
            search, status, audit_type: type,
            department_id: departmentId,
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">Manajemen Audit</h2>}>
            <Head title="Manajemen Audit" />
            <div className="py-10"><div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section className="flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-900 p-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-200">Audit Management</p>
                        <h1 className="mt-2 text-2xl font-bold">Register Audit</h1>
                        <p className="mt-1 text-sm text-slate-300">Audit internal, eksternal, pemasok, dan regulator dengan temuan dan CAPA.</p>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('audit.management.export') && <Link href={route('audits.export')} className="rounded-lg border border-white/30 px-4 py-2 text-sm font-semibold hover:bg-white/10">Ekspor CSV</Link>}
                        {permissions.has('audit.management.create') && <Link href={route('audits.create')} className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-900 hover:bg-indigo-50">Buat Audit</Link>}
                    </div>
                </section>

                <form onSubmit={submit} className="grid gap-3 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 md:grid-cols-6">
                    <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white md:col-span-2" />
                    <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Semua status</option>
                        {Object.entries(statusLabel).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                    </select>
                    <select value={type} onChange={(e) => setType(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Semua jenis</option>
                        {Object.entries(typeLabel).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                    </select>
                    <select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Semua department</option>
                        {departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                    </select>
                    <div className="flex gap-2 md:col-span-2">
                        <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" title="Dari tanggal" />
                        <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" title="Sampai tanggal" />
                    </div>
                    <div className="flex gap-2 md:col-span-6">
                        <button className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Terapkan</button>
                        <button type="button" onClick={() => router.get(route('audits.index'))} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Reset</button>
                    </div>
                </form>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800"><div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                        <thead className="bg-slate-50 dark:bg-gray-900"><tr>
                            {['Nomor Audit', 'Judul', 'Jenis', 'Status', 'Tanggal Jadwal', 'Auditor Utama', 'Temuan'].map((label) => (
                                <th key={label} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</th>
                            ))}
                        </tr></thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-gray-700">
                            {audits.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada audit"
                                            description="Mulai rencana dan laksanakan audit internal, eksternal, pemasok, atau regulator"
                                            action={
                                                permissions.has('audit.management.create') ? (
                                                    <Link
                                                        href={route('audits.create')}
                                                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                    >
                                                        Buat Audit
                                                    </Link>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : audits.data.map((item) => (
                                <tr key={item.id} className="cursor-pointer hover:bg-slate-50 dark:hover:bg-gray-700/60" onClick={() => router.get(route('audits.show', item.id))}>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><Link href={route('audits.show', item.id)} className="font-semibold text-indigo-600 hover:text-indigo-800" onClick={(e) => e.stopPropagation()}>{item.audit_number}</Link></td>
                                    <td className="max-w-md px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{item.title || 'Belum diberi judul'}<div className="mt-1 text-xs font-normal text-slate-400">{item.department?.name ?? 'Lintas department'}</div></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{typeLabel[item.audit_type ?? ''] ?? item.audit_type ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[item.status] ?? 'bg-slate-100 text-slate-700'}`}>{statusLabel[item.status] ?? item.status}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.scheduled_date ? new Date(item.scheduled_date).toLocaleDateString('id-ID') : '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.lead_auditor?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{item.findings_count}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div></div>
                <Pagination links={audits.links} />
            </div></div>
        </AuthenticatedLayout>
    );
}
