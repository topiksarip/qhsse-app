import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import FilterPanel from '@/Components/UI/FilterPanel';

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
        router.get('/assets', { search, site_id: siteId, category, status, safety_critical: safetyCritical }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch(''); setSiteId(''); setCategory(''); setStatus(''); setSafetyCritical('');
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
            active: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            inactive: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
            decommissioned: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
        return colors[stat] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    };

    const certificateStatus = {
        valid: { label: 'Sertifikat Valid', className: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' },
        expiring_soon: { label: 'Segera Kedaluwarsa', className: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200' },
        expiring_critical: { label: 'Kritis ≤ 7 Hari', className: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' },
        expired: { label: 'Sertifikat Expired', className: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200' },
    } as const;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Aset &amp; Peralatan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Asset &amp; Equipment Safety</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && (
                            <SecondaryButton size="sm" href={`/assets/export${exportQuery ? `?${exportQuery}` : ''}`}>Export</SecondaryButton>
                        )}
                        {can.create && (
                            <PrimaryButton size="sm" href="/assets/create">Add Asset</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Assets" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, siteId, category, status, safetyCritical].filter(v => v !== '').length}>
                        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <form onSubmit={handleSearch} className="grid grid-cols-1 gap-3 md:grid-cols-6">
                                <input type="text" placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">All Sites</option>
                                    {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                                </select>
                                <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">All Categories</option>
                                    {Object.entries(categories).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                </select>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">All Statuses</option>
                                    {Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                </select>
                                <select value={safetyCritical} onChange={(e) => setSafetyCritical(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Tingkat Kritis</option>
                                    <option value="1">Safety Critical</option>
                                    <option value="0">Non-Critical</option>
                                </select>
                                <div className="flex gap-2">
                                    <PrimaryButton type="submit">Filter</PrimaryButton>
                                    <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                                </div>
                            </form>
                        </div>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Asset Number</th>
                                <th className="px-4 py-3">Name</th>
                                <th className="px-4 py-3">Category</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3">Compliance</th>
                                <th className="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {assets.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="No assets found"
                                            description="Manage safety-critical equipment, machinery, and assets requiring inspection and certification"
                                            action={can.create ? <PrimaryButton href="/assets/create">Add Asset</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : assets.data.map((asset) => (
                                <tr key={asset.id} className={asset.safety_critical ? 'border-l-4 border-red-500' : 'hover:bg-slate-50 dark:hover:bg-gray-800'}>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <Link href={`/assets/${asset.id}`} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{asset.asset_number}</Link>
                                        {asset.safety_critical && (
                                            <span className="ml-2 rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">CRITICAL</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{asset.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{getCategoryLabel(asset.category)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{asset.site.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center">
                                        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${getStatusColor(asset.status)}`}>{getStatusLabel(asset.status)}</span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-col items-start gap-1">
                                            {asset.certificate_status ? (
                                                <span className={`rounded-full px-2 py-1 text-xs font-semibold ${certificateStatus[asset.certificate_status].className}`}>{certificateStatus[asset.certificate_status].label}</span>
                                            ) : (
                                                <span className="text-xs text-gray-500">Belum ada sertifikat</span>
                                            )}
                                            {asset.failed_inspections_without_capa > 0 && (
                                                <span className="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">{asset.failed_inspections_without_capa} inspeksi fail tanpa CAPA</span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={`/assets/${asset.id}`} className="text-emerald-600 hover:underline dark:text-emerald-400">View</Link>
                                        {can.create && (
                                        <DeleteWithConfirm
                                            routeName="assets.destroy"
                                            id={asset.id}
                                            permission="asset.management.delete"
                                            itemLabel={asset.asset_number}
                                            redirectTo="assets.index"
                                            asLink
                                        >
                                            Delete
                                        </DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {assets.links && (
                        <div className="flex items-center justify-between border-t border-slate-200 px-4 py-4 dark:border-gray-700">
                            <div className="text-sm text-gray-700 dark:text-gray-300">Showing {assets.from ?? 0} to {assets.to ?? 0} of {assets.total} results</div>
                            <div className="flex gap-1">
                                {assets.links.map((link: any, index: number) => (
                                    <Link key={index} href={link.url || '#'} className={`rounded border px-3 py-2 text-sm ${link.active ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 bg-white text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`} preserveState dangerouslySetInnerHTML={{ __html: link.label }} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
