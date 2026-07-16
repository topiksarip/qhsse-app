import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import FilterPanel from '@/Components/UI/FilterPanel';

interface Item {
    id: number;
    item_number: string;
    catalog: { name: string; catalog_code: string } | null;
    serial_number: string | null;
    track_type: string;
    quantity: number;
    status: string;
    condition: string;
    site: { name: string };
    department: { name: string } | null;
    expiry_date: string | null;
}

interface Filters {
    search: string;
    catalog_id: string;
    status: string;
    site_id: string;
    condition: string;
}

export default function Index({ auth, items, filters, sites, catalogs, statuses, conditions, can }: PageProps<{
    items: {
        data: Item[];
        links: any[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Filters;
    sites: Array<{ id: number; name: string }>;
    catalogs: Array<{ id: number; name: string; catalog_code: string }>;
    statuses: Record<string, string>;
    conditions: Record<string, string>;
    can: { create: boolean; export: boolean };
}>) {
    const [search, setSearch] = useState(filters.search || '');
    const [catalogId, setCatalogId] = useState(filters.catalog_id || '');
    const [status, setStatus] = useState(filters.status || '');
    const [siteId, setSiteId] = useState(filters.site_id || '');
    const [condition, setCondition] = useState(filters.condition || '');

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/apd/items', { search, catalog_id: catalogId, status, site_id: siteId, condition }, { preserveState: true });
    };
    const handleReset = () => {
        setSearch(''); setCatalogId(''); setStatus(''); setSiteId(''); setCondition('');
        router.get('/apd/items');
    };
    const exportQuery = new URLSearchParams({
        ...(search && { search }),
        ...(catalogId && { catalog_id: catalogId }),
        ...(status && { status }),
        ...(siteId && { site_id: siteId }),
        ...(condition && { condition }),
    }).toString();

    const statusColor: Record<string, string> = {
        in_stock: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
        issued: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        in_inspection: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
        damaged: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
        disposed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        lost: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-slate-600 dark:text-slate-400">APD / PPE</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Inventori APD</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && (
                            <SecondaryButton size="sm" href={`/apd/items/export${exportQuery ? `?${exportQuery}` : ''}`}>Export</SecondaryButton>
                        )}
                        {can.create && (
                            <PrimaryButton size="sm" href="/apd/items/create">Terima Stok</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Inventori APD" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, catalogId, status, siteId, condition].filter(v => v !== '').length}>
                        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <form onSubmit={handleSearch} className="grid grid-cols-1 gap-3 md:grid-cols-6">
                                <input type="text" placeholder="Cari no. item / serial..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                                <select value={catalogId} onChange={(e) => setCatalogId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Katalog</option>
                                    {catalogs.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                </select>
                                <select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Status</option>
                                    {Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                </select>
                                <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                <select value={condition} onChange={(e) => setCondition(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Kondisi</option>
                                    {Object.entries(conditions).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
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
                                <th className="px-4 py-3">No. Item</th>
                                <th className="px-4 py-3">Katalog</th>
                                <th className="px-4 py-3">Serial</th>
                                <th className="px-4 py-3 text-center">Qty</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3">Kondisi</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada item inventori"
                                            description="Terima stok APD dari katalog untuk mulai melacak unit/batch di gudang."
                                            action={can.create ? <PrimaryButton href="/apd/items/create">Terima Stok</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3"><Link href={`/apd/items/${item.id}`} className="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">{item.item_number}</Link></td>
                                    <td className="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{item.catalog?.name || '-'}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.serial_number || '-'}</td>
                                    <td className="px-4 py-3 text-center text-sm text-gray-500">{item.quantity}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{item.site.name}</td>
                                    <td className="px-4 py-3 text-center"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColor[item.status] || 'bg-gray-100 text-gray-800'}`}>{statuses[item.status] || item.status}</span></td>
                                    <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{conditions[item.condition] || item.condition}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm"><Link href={`/apd/items/${item.id}`} className="text-blue-600 hover:underline dark:text-blue-400">Lihat</Link></td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {items.links && (
                        <div className="flex items-center justify-between border-t border-slate-200 px-4 py-4 dark:border-gray-700">
                            <div className="text-sm text-gray-700 dark:text-gray-300">Menampilkan {items.from ?? 0} sampai {items.to ?? 0} dari {items.total} hasil</div>
                            <div className="flex gap-1">
                                {items.links.map((link: any, index: number) => (
                                    <Link key={index} href={link.url || '#'} className={`rounded border px-3 py-2 text-sm ${link.active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'} ${!link.url ? 'pointer-events-none opacity-50' : ''}`} preserveState dangerouslySetInnerHTML={{ __html: link.label }} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
