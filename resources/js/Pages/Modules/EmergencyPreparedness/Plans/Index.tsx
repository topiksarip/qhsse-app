import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyPlan, Site, PageProps, PaginatedData } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';

interface PlanIndexProps extends PageProps {
    plans: PaginatedData<EmergencyPlan>;
    filters: {
        search?: string;
        type?: string;
        site_id?: number;
    };
    sites: Site[];
    can: {
        create: boolean;
        export: boolean;
    };
}

const planTypeColors: Record<string, string> = {
    fire: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    medical: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    spill: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    evacuation: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    natural_disaster: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    security: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    other: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};

const planTypeLabels: Record<string, string> = {
    fire: 'Kebakaran',
    medical: 'Medis',
    spill: 'Tumpahan',
    evacuation: 'Evakuasi',
    natural_disaster: 'Bencana Alam',
    security: 'Keamanan',
    other: 'Lainnya',
};

export default function Index({ auth, plans, filters, sites, can }: PlanIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');

    const handleFilter = () => {
        router.get(route('emergency.plans.index'), {
            search: search || undefined,
            type: type || undefined,
            site_id: siteId || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setType('');
        setSiteId('');
        router.get(route('emergency.plans.index'));
    };

    const handleExport = () => {
        window.location.href = route('emergency.plans.export', filters);
    };

    const getTypeBadge = (planType: string) => {
        const colorClass = planTypeColors[planType] || planTypeColors.other;
        const label = planTypeLabels[planType] || planType;
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>
                {label}
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            Rencana Darurat
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola rencana kesiapsiagaan darurat: kebakaran, medis, tumpahan, evakuasi
                        </p>
                    </div>
                    {can.create && (
                        <Link
                            href={route('emergency.plans.create')}
                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            + Buat Rencana
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Rencana Darurat" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filter Bar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <input
                                        type="text"
                                        placeholder="🔍 Cari nomor, nama..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </div>
                                <div>
                                    <select
                                        value={type}
                                        onChange={(e) => setType(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="">Semua Tipe</option>
                                        <option value="fire">Kebakaran</option>
                                        <option value="medical">Medis</option>
                                        <option value="spill">Tumpahan</option>
                                        <option value="evacuation">Evakuasi</option>
                                        <option value="natural_disaster">Bencana Alam</option>
                                        <option value="security">Keamanan</option>
                                        <option value="other">Lainnya</option>
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
                    {plans.data.length > 0 && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-4">
                            <div className="p-4 flex justify-between items-center">
                                <div className="text-sm text-gray-700 dark:text-gray-300">
                                    Menampilkan {plans.from} - {plans.to} dari {plans.total} rencana
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
                            {plans.data.length === 0 ? (
                                <div className="py-12">
                                    <EmptyState
                                        title="Belum ada rencana darurat"
                                        description="Kelola rencana kesiapsiagaan: kebakaran, medis, tumpahan, evakuasi, bencana alam"
                                        action={
                                            can.create ? (
                                                <Link
                                                    href={route('emergency.plans.create')}
                                                    className="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                                                >
                                                    + Buat Rencana Pertama
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
                                                        Nama
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Tipe
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Site
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Kontak
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Aksi
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                {plans.data.map((plan) => (
                                                    <tr key={plan.id}>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-gray-100">
                                                            <Link
                                                                href={route('emergency.plans.show', plan.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                            >
                                                                {plan.plan_number}
                                                            </Link>
                                                        </td>
                                                        <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            <div className="max-w-xs truncate">
                                                                {plan.name}
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                            {getTypeBadge(plan.type)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {plan.site?.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {plan.contact_person?.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                            <div className="flex justify-center gap-2">
                                                                <Link
                                                                    href={route('emergency.plans.show', plan.id)}
                                                                    className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                                >
                                                                    Lihat
                                                                </Link>
                                                                {can.create && (
                                                                    <Link
                                                                        href={route('emergency.plans.edit', plan.id)}
                                                                        className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                                                    >
                                                                        Edit
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
                                    {plans.last_page > 1 && (
                                        <div className="mt-6 flex items-center justify-between">
                                            <div className="text-sm text-gray-700 dark:text-gray-300">
                                                Menampilkan {plans.from} - {plans.to} dari {plans.total} rencana
                                            </div>
                                            <div className="flex gap-2">
                                                {plans.current_page > 1 && (
                                                    <Link
                                                        href={route('emergency.plans.index', { ...filters, page: plans.current_page - 1 })}
                                                        className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        ‹ Sebelumnya
                                                    </Link>
                                                )}
                                                {[...Array(plans.last_page)].map((_, i) => (
                                                    <Link
                                                        key={i + 1}
                                                        href={route('emergency.plans.index', { ...filters, page: i + 1 })}
                                                        className={`px-3 py-1 border rounded-md text-sm ${
                                                            plans.current_page === i + 1
                                                                ? 'bg-red-600 text-white border-red-600'
                                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                        }`}
                                                    >
                                                        {i + 1}
                                                    </Link>
                                                ))}
                                                {plans.current_page < plans.last_page && (
                                                    <Link
                                                        href={route('emergency.plans.index', { ...filters, page: plans.current_page + 1 })}
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
