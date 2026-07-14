import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';

interface Asset {
    id: number;
    asset_number: string;
    name: string;
    category: string;
    serial_number: string | null;
    status: string;
    safety_critical: boolean;
    site: { id: number; name: string };
    area: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    next_inspection_date: string | null;
    certificate_status: 'valid' | 'expiring_soon' | 'expiring_critical' | 'expired' | null;
    failed_inspections_without_capa: number;
    created_at: string;
}

interface Filters {
    search: string;
    site_id: string;
    category: string;
    status: string;
    safety_critical: string;
}

export default function Index({ auth, assets, filters, sites, categories, statuses, can }: PageProps<{
    assets: {
        data: Asset[];
        links: any[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Filters;
    sites: Array<{ id: number; name: string }>;
    categories: Record<string, string>;
    statuses: Record<string, string>;
    can: { create: boolean; export: boolean };
}>) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [category, setCategory] = useState(filters.category || '');
    const [status, setStatus] = useState(filters.status || '');
    const [safetyCritical, setSafetyCritical] = useState(filters.safety_critical || '');

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/assets', {
            search,
            site_id: siteId,
            category,
            status,
            safety_critical: safetyCritical,
        }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        setSiteId('');
        setCategory('');
        setStatus('');
        setSafetyCritical('');
        router.get('/assets');
    };

    const getCategoryLabel = (cat: string) => categories[cat] || cat;
    const getStatusLabel = (stat: string) => statuses[stat] || stat;
    const exportQuery = new URLSearchParams({
        ...(search && { search }),
        ...(siteId && { site_id: siteId }),
        ...(category && { category }),
        ...(status && { status }),
        ...(safetyCritical && { safety_critical: safetyCritical }),
    }).toString();

    const getStatusColor = (stat: string) => {
        const colors: Record<string, string> = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-yellow-100 text-yellow-800',
            decommissioned: 'bg-gray-100 text-gray-800',
        };
        return colors[stat] || 'bg-gray-100 text-gray-800';
    };

    const certificateStatus = {
        valid: { label: 'Sertifikat Valid', className: 'bg-green-100 text-green-800' },
        expiring_soon: { label: 'Segera Kedaluwarsa', className: 'bg-yellow-100 text-yellow-800' },
        expiring_critical: { label: 'Kritis ≤ 7 Hari', className: 'bg-orange-100 text-orange-800' },
        expired: { label: 'Sertifikat Expired', className: 'bg-red-100 text-red-800' },
    } as const;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Asset & Equipment Safety
                    </h2>
                    <div className="flex space-x-2">
                        {can.export && (
                            <Link
                                href={`/assets/export${exportQuery ? `?${exportQuery}` : ''}`}
                                className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                            >
                                Export
                            </Link>
                        )}
                        {can.create && (
                            <Link
                                href="/assets/create"
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                            >
                                Add Asset
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Assets" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Search/Filter Form */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <form onSubmit={handleSearch} className="p-6">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-6">
                                <div>
                                    <input
                                        type="text"
                                        placeholder="Search..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm"
                                    />
                                </div>
                                <div>
                                    <select
                                        value={siteId}
                                        onChange={(e) => setSiteId(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm"
                                    >
                                        <option value="">All Sites</option>
                                        {sites.map(site => (
                                            <option key={site.id} value={site.id}>{site.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={category}
                                        onChange={(e) => setCategory(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm"
                                    >
                                        <option value="">All Categories</option>
                                        {Object.entries(categories).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm"
                                    >
                                        <option value="">All Statuses</option>
                                        {Object.entries(statuses).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={safetyCritical}
                                        onChange={(e) => setSafetyCritical(e.target.value)}
                                        className="w-full border-gray-300 rounded-md shadow-sm"
                                    >
                                        <option value="">Semua Tingkat Kritis</option>
                                        <option value="1">Safety Critical</option>
                                        <option value="0">Non-Critical</option>
                                    </select>
                                </div>
                                <div className="flex space-x-2">
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                    >
                                        Filter
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleReset}
                                        className="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Assets Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset Number</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compliance</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {assets.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="px-6 py-12">
                                                <EmptyState
                                                    title="No assets found"
                                                    description="Manage safety-critical equipment, machinery, and assets requiring inspection and certification"
                                                    action={
                                                        can.create ? (
                                                            <Link
                                                                href="/assets/create"
                                                                className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                            >
                                                                Add Asset
                                                            </Link>
                                                        ) : undefined
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    ) : assets.data.map((asset) => (
                                        <tr key={asset.id} className={asset.safety_critical ? 'border-l-4 border-red-500' : ''}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <Link href={`/assets/${asset.id}`} className="text-blue-600 hover:text-blue-900">
                                                    {asset.asset_number}
                                                </Link>
                                                {asset.safety_critical && (
                                                    <span className="ml-2 px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">
                                                        CRITICAL
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">{asset.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{getCategoryLabel(asset.category)}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{asset.site.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(asset.status)}`}>
                                                    {getStatusLabel(asset.status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex flex-col items-start gap-1">
                                                    {asset.certificate_status ? (
                                                        <span className={`rounded-full px-2 py-1 text-xs font-semibold ${certificateStatus[asset.certificate_status].className}`}>
                                                            {certificateStatus[asset.certificate_status].label}
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-gray-500">Belum ada sertifikat</span>
                                                    )}
                                                    {asset.failed_inspections_without_capa > 0 && (
                                                        <span className="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">
                                                            {asset.failed_inspections_without_capa} inspeksi fail tanpa CAPA
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <Link href={`/assets/${asset.id}`} className="text-blue-600 hover:text-blue-900">
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {assets.links && (
                            <div className="px-6 py-4 border-t border-gray-200">
                                <div className="flex justify-between items-center">
                                    <div className="text-sm text-gray-700">
                                        Showing {assets.from ?? 0} to {assets.to ?? 0} of {assets.total} results
                                    </div>
                                    <div className="flex space-x-1">
                                        {assets.links.map((link: any, index: number) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-2 border rounded ${
                                                    link.active
                                                        ? 'bg-blue-600 text-white border-blue-600'
                                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                                preserveState
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
