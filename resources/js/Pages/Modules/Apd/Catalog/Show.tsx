import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface Catalog {
    id: number;
    catalog_code: string;
    name: string;
    category: string;
    track_type: string;
    sku: string | null;
    manufacturer: string | null;
    model: string | null;
    standard: string | null;
    size: string | null;
    protection_level: string | null;
    default_lifespan_months: number | null;
    inspection_interval_days: number | null;
    default_unit_cost: number | null;
    min_stock: number;
    reorder_point: number;
    is_active: boolean;
    description: string | null;
    items_count: number;
    active_quantity: number;
    creator?: { name: string };
}

interface Item {
    id: number;
    item_number: string;
    serial_number: string | null;
    status: string;
    quantity: number;
    site: { name: string };
    department: { name: string } | null;
}

export default function Show({ catalog, items, activities, auditLogs, can }: PageProps<{
    catalog: Catalog;
    items: { data: Item[]; links: any[] };
    activities: Array<{ description: string; created_at: string }>;
    auditLogs: Array<{ event: string; created_at: string }>;
    can: { update: boolean; delete: boolean };
}>) {
    const categories: Record<string, string> = {
        head_protection: 'Pelindung Kepala',
        eye_face_protection: 'Pelindung Mata & Wajah',
        hearing_protection: 'Pelindung Pendengaran',
        respiratory_protection: 'Pelindung Pernapasan',
        hand_protection: 'Sarung Tangan',
        foot_protection: 'Pelindung Kaki',
        body_protection: 'Pelindung Tubuh',
        fall_protection: 'Pelindung Jatuh',
        other: 'Lainnya',
    };
    const statusesItem: Record<string, string> = {
        in_stock: 'Di Gudang',
        issued: 'Terissue',
        in_inspection: 'Dalam Inspeksi',
        damaged: 'Rusak',
        disposed: 'Dimusnahkan',
        lost: 'Hilang',
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/catalogs" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Katalog
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-white">{catalog.catalog_code} — {catalog.name}</h2>
                </div>
            }
        >
            <Head title={`APD ${catalog.catalog_code}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-2">
                        {can.update && <PrimaryButton size="sm" href={`/apd/catalogs/${catalog.id}/edit`}>Edit</PrimaryButton>}
                        <SecondaryButton size="sm" href="/apd/items/create">Terima Stok</SecondaryButton>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-2 bg-white shadow-sm rounded-lg p-6 dark:bg-gray-900">
                            <h3 className="font-semibold text-lg mb-4 text-slate-800 dark:text-white">Detail Katalog</h3>
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div><dt className="text-gray-500">Kategori</dt><dd className="text-slate-800 dark:text-slate-100">{categories[catalog.category] || catalog.category}</dd></div>
                                <div><dt className="text-gray-500">Tipe Pelacakan</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.track_type === 'serial' ? 'Per-Serial' : 'Per-Batch'}</dd></div>
                                <div><dt className="text-gray-500">SKU</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.sku || '-'}</dd></div>
                                <div><dt className="text-gray-500">Manufacturer</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.manufacturer || '-'}</dd></div>
                                <div><dt className="text-gray-500">Model</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.model || '-'}</dd></div>
                                <div><dt className="text-gray-500">Standard</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.standard || '-'}</dd></div>
                                <div><dt className="text-gray-500">Level Perlindungan</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.protection_level || '-'}</dd></div>
                                <div><dt className="text-gray-500">Masa Pakai</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.default_lifespan_months ? `${catalog.default_lifespan_months} bulan` : '-'}</dd></div>
                                <div><dt className="text-gray-500">Interval Inspeksi</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.inspection_interval_days ? `${catalog.inspection_interval_days} hari` : '-'}</dd></div>
                                <div><dt className="text-gray-500">Biaya Satuan</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.default_unit_cost ?? '-'}</dd></div>
                                <div><dt className="text-gray-500">Stok Minimum</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.min_stock}</dd></div>
                                <div><dt className="text-gray-500">Titik Pemesanan</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.reorder_point}</dd></div>
                                <div><dt className="text-gray-500">Total Item</dt><dd className="text-slate-800 dark:text-slate-100">{catalog.items_count}</dd></div>
                                <div><dt className="text-gray-500">Stok Aktif</dt><dd className={catalog.active_quantity < catalog.min_stock ? 'text-red-600 font-semibold' : 'text-slate-800 dark:text-slate-100'}>{catalog.active_quantity}</dd></div>
                            </dl>
                            {catalog.description && (
                                <div className="mt-4"><dt className="text-gray-500 text-sm">Deskripsi</dt><dd className="text-slate-800 dark:text-slate-100 text-sm mt-1">{catalog.description}</dd></div>
                            )}
                        </div>

                        <div className="bg-white shadow-sm rounded-lg p-6 dark:bg-gray-900">
                            <h3 className="font-semibold text-lg mb-4 text-slate-800 dark:text-white">Aktivitas</h3>
                            <ul className="space-y-3 text-sm">
                                {activities.length === 0 && <li className="text-gray-500">Belum ada aktivitas.</li>}
                                {activities.map((a, i) => (
                                    <li key={i} className="border-l-2 border-blue-200 pl-3">
                                        <p className="text-slate-700 dark:text-slate-200">{a.description}</p>
                                        <p className="text-xs text-gray-400">{a.created_at}</p>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>

                    <div className="bg-white shadow-sm rounded-lg p-6 dark:bg-gray-900">
                        <h3 className="font-semibold text-lg mb-4 text-slate-800 dark:text-white">Item Inventori ({items.data.length})</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="text-left text-gray-500 border-b dark:border-gray-700">
                                        <th className="px-3 py-2">No. Item</th>
                                        <th className="px-3 py-2">Serial</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2 text-center">Qty</th>
                                        <th className="px-3 py-2">Site</th>
                                        <th className="px-3 py-2">Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.length === 0 && (
                                        <tr><td colSpan={6} className="px-3 py-6 text-center text-gray-500">Belum ada item stok untuk katalog ini.</td></tr>
                                    )}
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="border-b dark:border-gray-800">
                                            <td className="px-3 py-2"><Link href={`/apd/items/${item.id}`} className="text-blue-600 hover:underline dark:text-blue-400">{item.item_number}</Link></td>
                                            <td className="px-3 py-2">{item.serial_number || '-'}</td>
                                            <td className="px-3 py-2">{statusesItem[item.status] || item.status}</td>
                                            <td className="px-3 py-2 text-center">{item.quantity}</td>
                                            <td className="px-3 py-2">{item.site.name}</td>
                                            <td className="px-3 py-2">{item.department?.name || '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
