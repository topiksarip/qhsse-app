import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, TrainingRecord } from '@/types';
import { useState } from 'react';
import StatusBadge from '@/Components/Training/StatusBadge';
import ResultBadge from '@/Components/Training/ResultBadge';
import ExpiryIndicator from '@/Components/Training/ExpiryIndicator';
import { format, parseISO } from 'date-fns';
import EmptyState from '@/Components/UI/EmptyState';

interface RecordsIndexProps extends PageProps {
    records: PaginatedData<TrainingRecord>;
    filters: {
        search?: string;
        employee_id?: string;
        program_id?: string;
        status?: string;
        expiring_soon?: string;
    };
    can: {
        create: boolean;
        update: boolean;
        view: boolean;
    };
}

export default function Index({ auth, records, filters, can }: RecordsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [expiringSoon, setExpiringSoon] = useState(filters.expiring_soon || '');

    const handleFilter = () => {
        router.get(route('training.records.index'), {
            search,
            status: status || undefined,
            expiring_soon: expiringSoon || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setExpiringSoon('');
        router.get(route('training.records.index'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            Record Pelatihan
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola record dan riwayat pelatihan karyawan
                        </p>
                    </div>
                    {can.create && (
                        <Link
                            href={route('training.records.create')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            + Buat Record
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Record Pelatihan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Filter Bar */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="md:col-span-2">
                                <input
                                    type="text"
                                    placeholder="🔍 Cari nomor, karyawan, program..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <select
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Status: Semua</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="expired">Expired</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <select
                                    value={expiringSoon}
                                    onChange={(e) => setExpiringSoon(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Expiry: Semua</option>
                                    <option value="30">≤ 30 hari</option>
                                    <option value="60">≤ 60 hari</option>
                                    <option value="90">≤ 90 hari</option>
                                </select>
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <button
                                onClick={handleFilter}
                                className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                            >
                                Filter
                            </button>
                            <button
                                onClick={handleReset}
                                className="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    {/* Quick Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-blue-600 dark:text-blue-400 font-medium">Scheduled</p>
                                    <p className="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">
                                        {records.data.filter(r => r.status === 'scheduled').length}
                                    </p>
                                </div>
                                <div className="text-3xl">🔵</div>
                            </div>
                        </div>
                        <div className="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-yellow-600 dark:text-yellow-400 font-medium">In Progress</p>
                                    <p className="text-2xl font-bold text-yellow-700 dark:text-yellow-300 mt-1">
                                        {records.data.filter(r => r.status === 'in_progress').length}
                                    </p>
                                </div>
                                <div className="text-3xl">🟡</div>
                            </div>
                        </div>
                        <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-green-600 dark:text-green-400 font-medium">Completed</p>
                                    <p className="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">
                                        {records.data.filter(r => r.status === 'completed').length}
                                    </p>
                                </div>
                                <div className="text-3xl">🟢</div>
                            </div>
                        </div>
                        <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-red-600 dark:text-red-400 font-medium">Expired</p>
                                    <p className="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">
                                        {records.data.filter(r => r.status === 'expired').length}
                                    </p>
                                </div>
                                <div className="text-3xl">🔴</div>
                            </div>
                        </div>
                    </div>

                    {/* Records Table */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Menampilkan {records.from || 0}–{records.to || 0} dari {records.total} record
                            </p>
                        </div>

                        {records.data.length === 0 ? (
                            <div className="p-12">
                                <EmptyState
                                    title="Belum ada record pelatihan"
                                    description="Kelola record pelatihan, hasil, sertifikat, dan tracking kompetensi karyawan"
                                    action={
                                        can.create ? (
                                            <Link
                                                href={route('training.records.create')}
                                                className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                            >
                                                + Buat Record Pertama
                                            </Link>
                                        ) : undefined
                                    }
                                />
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Nomor
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Karyawan
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Program
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Tanggal
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Result
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Expiry
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {records.data.map((record) => (
                                            <tr key={record.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className="font-mono text-sm text-gray-900 dark:text-gray-100">
                                                        {record.training_number}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="text-sm">
                                                        <div className="font-medium text-gray-900 dark:text-gray-100">
                                                            {record.employee?.name || 'N/A'}
                                                        </div>
                                                        <div className="text-gray-500 dark:text-gray-400 text-xs">
                                                            {record.employee?.employee_number || '-'}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="text-sm max-w-xs truncate">
                                                        <div className="font-medium text-gray-900 dark:text-gray-100">
                                                            {record.program?.name || 'N/A'}
                                                        </div>
                                                        <div className="text-gray-500 dark:text-gray-400 text-xs">
                                                            {record.program?.code || '-'}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                                                    {format(parseISO(record.start_date), 'dd/MM/yyyy')}
                                                    {record.end_date && (
                                                        <>
                                                            <br />
                                                            <span className="text-xs text-gray-500">
                                                                s/d {format(parseISO(record.end_date), 'dd/MM/yyyy')}
                                                            </span>
                                                        </>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center">
                                                    <StatusBadge status={record.status} />
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center">
                                                    <ResultBadge result={record.result} />
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center">
                                                    <ExpiryIndicator 
                                                        expiryDate={record.expiry_date} 
                                                        status={record.status} 
                                                    />
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center text-sm">
                                                    <div className="flex items-center justify-center gap-2">
                                                        {can.view && (
                                                            <Link
                                                                href={route('training.records.show', record.id)}
                                                                className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                            >
                                                                👁 View
                                                            </Link>
                                                        )}
                                                        {can.update && (
                                                            <Link
                                                                href={route('training.records.edit', record.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                            >
                                                                ✏ Edit
                                                            </Link>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Pagination */}
                        {records.last_page > 1 && (
                            <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-center gap-2">
                                {records.current_page > 1 && (
                                    <Link
                                        href={route('training.records.index', { ...filters, page: records.current_page - 1 })}
                                        className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        ‹ Sebelumnya
                                    </Link>
                                )}
                                {Array.from({ length: Math.min(records.last_page, 10) }, (_, i) => {
                                    let page: number;
                                    if (records.last_page <= 10) {
                                        page = i + 1;
                                    } else if (records.current_page <= 5) {
                                        page = i + 1;
                                    } else if (records.current_page >= records.last_page - 4) {
                                        page = records.last_page - 9 + i;
                                    } else {
                                        page = records.current_page - 4 + i;
                                    }
                                    return (
                                        <Link
                                            key={page}
                                            href={route('training.records.index', { ...filters, page })}
                                            className={`px-3 py-1 text-sm border rounded ${
                                                page === records.current_page
                                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                                    : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            }`}
                                        >
                                            {page}
                                        </Link>
                                    );
                                })}
                                {records.current_page < records.last_page && (
                                    <Link
                                        href={route('training.records.index', { ...filters, page: records.current_page + 1 })}
                                        className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Berikutnya ›
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
