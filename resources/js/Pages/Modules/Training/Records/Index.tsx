import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, TrainingRecord } from '@/types';
import { useState } from 'react';
import StatusBadge from '@/Components/Training/StatusBadge';
import ResultBadge from '@/Components/Training/ResultBadge';
import ExpiryIndicator from '@/Components/Training/ExpiryIndicator';
import { format, parseISO } from 'date-fns';
import EmptyState from '@/Components/UI/EmptyState';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';

interface RecordsIndexProps extends PageProps {
    records: PaginatedData<TrainingRecord>;
    filters: { search?: string; employee_id?: string; program_id?: string; status?: string; expiring_soon?: string };
    can: { create: boolean; update: boolean; view: boolean };
}

export default function Index({ auth, records, filters, can }: RecordsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [expiringSoon, setExpiringSoon] = useState(filters.expiring_soon || '');

    const handleFilter = () => {
        router.get(route('training.records.index'), { search, status: status || undefined, expiring_soon: expiringSoon || undefined }, { preserveState: true, preserveScroll: true });
    };
    const handleReset = () => { setSearch(''); setStatus(''); setExpiringSoon(''); router.get(route('training.records.index')); };

    const count = (s: string) => records.data.filter(r => r.status === s).length;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Pelatihan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Record Pelatihan</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.create && <PrimaryButton size="sm" href={route('training.records.create')}>+ Buat Record</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Record Pelatihan" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input type="text" placeholder="🔍 Cari nomor, karyawan, program..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyPress={(e) => e.key === 'Enter' && handleFilter()} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Status: Semua</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <select value={expiringSoon} onChange={(e) => setExpiringSoon(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Expiry: Semua</option>
                                <option value="30">≤ 30 hari</option>
                                <option value="60">≤ 60 hari</option>
                                <option value="90">≤ 90 hari</option>
                            </select>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="button" onClick={handleFilter}>Filter</PrimaryButton>
                            <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20"><p className="text-sm font-medium text-blue-600 dark:text-blue-400">Scheduled</p><p className="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{count('scheduled')}</p></div>
                        <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20"><p className="text-sm font-medium text-yellow-600 dark:text-yellow-400">In Progress</p><p className="mt-1 text-2xl font-bold text-yellow-700 dark:text-yellow-300">{count('in_progress')}</p></div>
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20"><p className="text-sm font-medium text-green-600 dark:text-green-400">Completed</p><p className="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">{count('completed')}</p></div>
                        <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20"><p className="text-sm font-medium text-red-600 dark:text-red-400">Expired</p><p className="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">{count('expired')}</p></div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Karyawan</th>
                                <th className="px-4 py-3">Program</th>
                                <th className="px-4 py-3 text-center">Tanggal</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Result</th>
                                <th className="px-4 py-3 text-center">Expiry</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {records.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState title="Belum ada record pelatihan" description="Kelola record pelatihan, hasil, sertifikat, dan tracking kompetensi karyawan" action={can.create ? <PrimaryButton href={route('training.records.create')}>+ Buat Record Pertama</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : records.data.map((record) => (
                                <tr key={record.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm text-slate-900 dark:text-slate-100">{record.training_number}</td>
                                    <td className="px-4 py-3">
                                        <div className="text-sm"><div className="font-medium text-slate-900 dark:text-slate-100">{record.employee?.name || 'N/A'}</div><div className="text-xs text-gray-500">{record.employee?.employee_no || '-'}</div></div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="max-w-xs truncate text-sm"><div className="font-medium text-slate-900 dark:text-slate-100">{record.training_program?.name || 'N/A'}</div><div className="text-xs text-gray-500">{record.training_program?.code || '-'}</div></div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-900 dark:text-slate-100">
                                        {format(parseISO(record.start_date), 'dd/MM/yyyy')}
                                        {record.end_date && (<><br /><span className="text-xs text-gray-500">s/d {format(parseISO(record.end_date), 'dd/MM/yyyy')}</span></>)}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center"><StatusBadge status={record.status} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center"><ResultBadge result={record.result} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center"><ExpiryIndicator expiryDate={record.expiry_date} status={record.status} /></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <div className="flex items-center justify-center gap-2">
                                            {can.view && <Link href={route('training.records.show', record.id)} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">👁 View</Link>}
                                            {can.update && <Link href={route('training.records.edit', record.id)} className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">✏ Edit</Link>}
                                            <DeleteWithConfirm routeName="training.records.destroy" id={record.id} permission="training.records.delete" itemLabel={record.training_number} asLink className="text-red-600 hover:underline dark:text-red-400">🗑</DeleteWithConfirm>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {records.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {records.current_page > 1 && <Link href={route('training.records.index', { ...filters, page: records.current_page - 1 })} className="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-800">‹ Sebelumnya</Link>}
                            {Array.from({ length: Math.min(records.last_page, 10) }, (_, i) => {
                                let page: number;
                                if (records.last_page <= 10) page = i + 1;
                                else if (records.current_page <= 5) page = i + 1;
                                else if (records.current_page >= records.last_page - 4) page = records.last_page - 9 + i;
                                else page = records.current_page - 4 + i;
                                return <Link key={page} href={route('training.records.index', { ...filters, page })} className={`px-3 py-1 text-sm border rounded ${page === records.current_page ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-800'}`}>{page}</Link>;
                            })}
                            {records.current_page < records.last_page && <Link href={route('training.records.index', { ...filters, page: records.current_page + 1 })} className="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-800">Berikutnya ›</Link>}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
