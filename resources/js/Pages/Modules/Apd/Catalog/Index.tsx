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

interface Catalog {
    id: number;
    catalog_code: string;
    name: string;
    category: string;
    track_type: string;
    protection_level: string | null;
    min_stock: number;
    active_quantity: number;
    low_stock: boolean;
    is_active: boolean;
    items_count?: number;
}

interface Filters {
    search: string;
    category: string;
    track_type: string;
    is_active: string;
}

export default function Index({ auth, catalogs, filters, categories, trackTypes, can }: PageProps<{
    catalogs: {
        data: Catalog[];
        links: any[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Filters;
    categories: Record<string, string>;
    trackTypes: Record<string, string>;
    can: { create: boolean; export: boolean; delete: boolean };
}>) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');
    const [trackType, setTrackType] = useState(filters.track_type || '');
    const [isActive, setIsActive] = useState(filters.is_active || '');

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/apd/catalogs', { search, category, track_type: trackType, is_active: isActive }, { preserveState: true });
    };
    const handleReset = () => {
        setSearch(''); setCategory(''); setTrackType(''); setIsActive('');
        router.get('/apd/catalogs');
    };

    const getCategoryLabel = (c: string) => categories[c] || c;
    const exportQuery = new URLSearchParams({
        ...(search && { search }),
        ...(category && { category }),
        ...(trackType && { track_type: trackType }),
        ...(isActive && { is_active: isActive }),
    }).toString();

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-slate-600 dark:text-slate-400">APD / PPE</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Katalog APD</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && (
                            <SecondaryButton size="sm" href={`/apd/catalogs/export${exportQuery ? `?${exportQuery}` : ''}`}>Export</SecondaryButton>
                        )}
                        {can.create && (
                            <PrimaryButton size="sm" href="/apd/catalogs/create">Tambah Katalog</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Katalog APD" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, category, trackType, isActive].filter(v => v !== '').length}>
                        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <form onSubmit={handleSearch} className="grid grid-cols-1 gap-3 md:grid-cols-5">
                                <input type="text" placeholder="Cari kode / nama..." value={search} onChange={(e) => setSearch(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                                <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Kategori</option>
                                    {Object.entries(categories).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                </select>
                                <select value={trackType} onChange={(e) => setTrackType(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Tipe</option>
                                    {Object.entries(trackTypes).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                </select>
                                <select value={isActive} onChange={(e) => setIsActive(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
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
                                <th className="px-4 py-3">Kode</th>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Kategori</th>
                                <th className="px-4 py-3 text-center">Tipe</th>
                                <th className="px-4 py-3 text-center">Stok Aktif</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {catalogs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada katalog APD"
                                            description="Buat katalog jenis APD (helm, sepatu, sarung tangan, respirator, dll) untuk mulai mengelola inventori."
                                            action={can.create ? <PrimaryButton href="/apd/catalogs/create">Tambah Katalog</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : catalogs.data.map((c) => (
                                <tr key={c.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <Link href={`/apd/catalogs/${c.id}`} className="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">{c.catalog_code}</Link>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{c.name}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{getCategoryLabel(c.category)}</td>
                                    <td className="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{c.track_type === 'serial' ? 'Per-Serial' : 'Per-Batch'}</td>
                                    <td className="px-4 py-3 text-center">
                                        {c.low_stock ? (
                                            <span className="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">{c.active_quantity} (Rendah)</span>
                                        ) : (
                                            <span className="text-sm text-gray-500">{c.active_quantity}</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${c.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'}`}>{c.is_active ? 'Aktif' : 'Nonaktif'}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={`/apd/catalogs/${c.id}`} className="text-blue-600 hover:underline dark:text-blue-400">Lihat</Link>
                                        {can.delete && (
                                            <DeleteWithConfirm
                                                routeName="apd.catalogs.destroy"
                                                id={c.id}
                                                permission="apd.delete"
                                                itemLabel={c.catalog_code}
                                                redirectTo="apd.catalogs.index"
                                                asLink
                                            >Hapus</DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {catalogs.links && (
                        <div className="flex items-center justify-between border-t border-slate-200 px-4 py-4 dark:border-gray-700">
                            <div className="text-sm text-gray-700 dark:text-gray-300">Menampilkan {catalogs.from ?? 0} sampai {catalogs.to ?? 0} dari {catalogs.total} hasil</div>
                            <div className="flex gap-1">
                                {catalogs.links.map((link: any, index: number) => (
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
