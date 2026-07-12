import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyDrill, EmergencyPlan, Site, PageProps, PaginatedData } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';

interface DrillIndexProps extends PageProps {
    drills: PaginatedData<EmergencyDrill & { emergency_plan: EmergencyPlan }>;
    filters: {
        search?: string;
        status?: string;
        result?: string;
        site_id?: number;
        from?: string;
        to?: string;
    };
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
        execute: boolean;
    };
}

const drillStatusColors: Record<string, string> = {
    scheduled: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    executed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
};

const drillStatusLabels: Record<string, string> = {
    scheduled: 'Terjadwal',
    executed: 'Selesai',
};

const drillResultColors: Record<string, string> = {
    pass: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    fail: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    needs_improvement: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
};

const drillResultLabels: Record<string, string> = {
    pass: 'Lulus',
    fail: 'Gagal',
    needs_improvement: 'Perlu Perbaikan',
};

export default function Index({ auth, drills, filters, sites, can }: DrillIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [result, setResult] = useState(filters.result || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');
    const [fromDate, setFromDate] = useState(filters.from || '');
    const [toDate, setToDate] = useState(filters.to || '');

    const handleFilter = () => {
        router.get(route('emergency.drills.index'), {
            search: search || undefined,
            status: status || undefined,
            result: result || undefined,
            site_id: siteId || undefined,
            from: fromDate || undefined,
            to: toDate || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setResult('');
        setSiteId('');
        setFromDate('');
        setToDate('');
        router.get(route('emergency.drills.index'));
    };

    const handleExport = () => {
        window.location.href = route('emergency.drills.export', filters);
    };

    const getStatusBadge = (drillStatus: string) => {
        const colorClass = drillStatusColors[drillStatus] || drillStatusColors.scheduled;
        const label = drillStatusLabels[drillStatus] || drillStatus;
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>{label}</span>;
    };

    const getResultBadge = (drillResult: 'pass' | 'fail' | 'needs_improvement' | null) => {
        if (!drillResult) return <span className="text-gray-400">—</span>;
        const colorClass = drillResultColors[drillResult] || 'bg-gray-100 text-gray-800';
        const label = drillResultLabels[drillResult] || drillResult;
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>{label}</span>;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            Latihan Darurat
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola latihan darurat: penjadwalan dan pelacakan eksekusi
                        </p>
                    </div>
                    {can.create && (
                        <Link
                            href={route('emergency.drills.create')}
                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            + Jadwalkan Latihan
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Latihan Darurat" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filter Bar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="md:col-span-2">
                                    <input
                                        type="text"
                                        placeholder="🔍 Cari nomor..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                <div>
                                    <select
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="">Semua Status</option>
                                        <option value="scheduled">Terjadwal</option>
                                        <option value="executed">Selesai</option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={result}
                                        onChange={(e) => setResult(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="">Semua Hasil</option>
                                        <option value="pass">Lulus</option>
                                        <option value="fail">Gagal</option>
                                        <option value="needs_improvement">Perlu Perbaikan</option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={siteId}
                                        onChange={(e) => setSiteId(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="">Semua Site</option>
                                        {sites.map((site) => (
                                            <option key={site.id} value={site.id}>
                                                {site.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari:</label>
                                    <input
                                        type="date"
                                        value={fromDate}
                                        onChange={(e) => setFromDate(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai:</label>
                                    <input
                                        type="date"
                                        value={toDate}
                                        onChange={(e) => setToDate(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </div>
                            </div>
                            <div className="mt-4 flex gap-2">
                                <button
                                    onClick={handleFilter}
                                    className="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Filter
                                </button>
                                <button
                                    onClick={handleReset}
                                    className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Toolbar */}
                    {drills.data.length > 0 && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-4">
                            <div className="p-4 flex justify-between items-center">
                                <div className="text-sm text-gray-700 dark:text-gray-300">
                                    Menampilkan {drills.from} - {drills.to} dari {drills.total} latihan
                                </div>
                                {can.export && (
                                    <button
                                        onClick={handleExport}
                                        className="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                    >
                                        ⬇ Export CSV
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Table */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {drills.data.length === 0 ? (
                                <div className="py-12">
                                    <EmptyState
                                        title="Belum ada latihan darurat"
                                        description="Jadwalkan dan dokumentasi latihan kebakaran, evakuasi, dan kesiapsiagaan darurat"
                                        action={
                                            can.create ? (
                                                <Link
                                                    href={route('emergency.drills.create')}
                                                    className="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                                                >
                                                    + Jadwalkan Latihan Pertama
                                                </Link>
                                            ) : undefined
                                        }
                                    />
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead className="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Nomor
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Rencana
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Terjadwal
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Eksekusi
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Hasil
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Aksi
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                {drills.data.map((drill) => (
                                                    <tr key={drill.id}>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-gray-100">
                                                            <Link
                                                                href={route('emergency.drills.show', drill.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                            >
                                                                {drill.drill_number}
                                                            </Link>
                                                        </td>
                                                        <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            <div className="max-w-xs truncate">
                                                                {drill.emergency_plan?.name}
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                                                            {drill.scheduled_date}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                                                            {drill.executed_date || '—'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                            {getResultBadge(drill.result ?? null)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                            {getStatusBadge(drill.status)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                            <div className="flex justify-center gap-2">
                                                                <Link
                                                                    href={route('emergency.drills.show', drill.id)}
                                                                    className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                                >
                                                                    Lihat
                                                                </Link>
                                                                {drill.status === 'scheduled' && can.create && (
                                                                    <Link
                                                                        href={route('emergency.drills.edit', drill.id)}
                                                                        className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                                    >
                                                                        Edit
                                                                    </Link>
                                                                )}
                                                                {drill.status === 'scheduled' && can.execute && (
                                                                    <Link
                                                                        href={route('emergency.drills.execute', drill.id)}
                                                                        className="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                                    >
                                                                        Eksekusi
                                                                    </Link>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Pagination */}
                                    {drills.last_page > 1 && (
                                        <div className="mt-6 flex items-center justify-between">
                                            <div className="text-sm text-gray-700 dark:text-gray-300">
                                                Menampilkan {drills.from} - {drills.to} dari {drills.total} latihan
                                            </div>
                                            <div className="flex gap-2">
                                                {drills.current_page > 1 && (
                                                    <Link
                                                        href={route('emergency.drills.index', { ...filters, page: drills.current_page - 1 })}
                                                        className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        ‹ Sebelumnya
                                                    </Link>
                                                )}
                                                {[...Array(drills.last_page)].map((_, i) => (
                                                    <Link
                                                        key={i + 1}
                                                        href={route('emergency.drills.index', { ...filters, page: i + 1 })}
                                                        className={`px-3 py-1 border rounded-md text-sm ${
                                                            drills.current_page === i + 1
                                                                ? 'bg-red-600 text-white border-red-600'
                                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                        }`}
                                                    >
                                                        {i + 1}
                                                    </Link>
                                                ))}
                                                {drills.current_page < drills.last_page && (
                                                    <Link
                                                        href={route('emergency.drills.index', { ...filters, page: drills.current_page + 1 })}
                                                        className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Berikutnya ›
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
