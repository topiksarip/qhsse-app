import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import FilterPanel from '@/Components/UI/FilterPanel';
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
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Audit</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Manajemen Audit</h2>
                    </div>
                    <div className="flex gap-2">
                        {permissions.has('audit.management.export') && (
                            <SecondaryButton size="sm" href={route('audits.export')}>Ekspor CSV</SecondaryButton>
                        )}
                        {permissions.has('audit.management.create') && (
                            <PrimaryButton size="sm" href={route('audits.create')}>Buat Audit</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Manajemen Audit" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, status, type, departmentId, dateFrom, dateTo].filter(v => v !== '').length}>
                        <form onSubmit={submit} className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-6">
                        <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nomor atau judul..." className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Semua status</option>
                            {Object.entries(statusLabel).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                        <select value={type} onChange={(e) => setType(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Semua jenis</option>
                            {Object.entries(typeLabel).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                        </select>
                        <select value={departmentId} onChange={(e) => setDepartmentId(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                            <option value="">Semua department</option>
                            {departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                        </select>
                        <div className="flex gap-2 md:col-span-2">
                            <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" title="Dari tanggal" />
                            <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" title="Sampai tanggal" />
                        </div>
                        <div className="flex gap-2 md:col-span-6">
                            <PrimaryButton type="submit">Terapkan</PrimaryButton>
                            <SecondaryButton onClick={() => router.get(route('audits.index'))}>Reset</SecondaryButton>
                        </div>
                        </form>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor Audit</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Jenis</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Tanggal Jadwal</th>
                                <th className="px-4 py-3">Auditor Utama</th>
                                <th className="px-4 py-3 text-center">Temuan</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {audits.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada audit"
                                            description="Mulai rencana dan laksanakan audit internal, eksternal, pemasok, atau regulator"
                                            action={
                                                permissions.has('audit.management.create') ? (
                                                    <PrimaryButton href={route('audits.create')}>Buat Audit</PrimaryButton>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : audits.data.map((item) => (
                                <tr key={item.id} className="cursor-pointer hover:bg-slate-50 dark:hover:bg-gray-800/60" onClick={() => router.get(route('audits.show', item.id))}>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        <Link href={route('audits.show', item.id)} className="font-semibold text-emerald-600 hover:text-emerald-800 dark:text-emerald-400" onClick={(e) => e.stopPropagation()}>{item.audit_number}</Link>
                                    </td>
                                    <td className="max-w-md px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{item.title || 'Belum diberi judul'}<div className="mt-1 text-xs font-normal text-slate-400">{item.department?.name ?? 'Lintas department'}</div></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{typeLabel[item.audit_type ?? ''] ?? item.audit_type ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[item.status] ?? 'bg-slate-100 text-slate-700'}`}>{statusLabel[item.status] ?? item.status}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.scheduled_date ? new Date(item.scheduled_date).toLocaleDateString('id-ID') : '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{item.lead_auditor?.name ?? '-'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-600 dark:text-slate-300">{item.findings_count}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm" onClick={(e) => e.stopPropagation()}>
                                        <DeleteWithConfirm
                                            routeName="audits.destroy"
                                            id={item.id}
                                            permission="audits.delete"
                                            itemLabel={item.audit_number}
                                            asLink
                                            className="text-red-600 hover:underline dark:text-red-400"
                                        >
                                            🗑 Hapus
                                        </DeleteWithConfirm>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>
                    <Pagination links={audits.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
