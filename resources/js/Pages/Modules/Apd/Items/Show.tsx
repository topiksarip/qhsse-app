import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface Item {
    id: number;
    item_number: string;
    catalog: { name: string; catalog_code: string } | null;
    track_type: string;
    serial_number: string | null;
    quantity: number;
    unit_cost: number | null;
    status: string;
    condition: string;
    site: { name: string };
    area: { name: string } | null;
    department: { name: string } | null;
    storage_location: string | null;
    manufacture_date: string | null;
    purchase_date: string | null;
    received_date: string | null;
    expiry_date: string | null;
    next_inspection_date: string | null;
    holder_type: string | null;
    holder_id: number | null;
    notes: string | null;
    creator?: { name: string };
}

export default function Show({ item, comments, activities, auditLogs, can }: PageProps<{
    item: Item;
    comments: Array<{ id: number; body: string; author: { name: string }; created_at: string }>;
    activities: Array<{ description: string; created_at: string }>;
    auditLogs: Array<{ event: string; created_at: string }>;
    can: { update: boolean; issue: boolean };
}>) {
    const statuses: Record<string, string> = {
        in_stock: 'Di Gudang',
        issued: 'Terissue',
        in_inspection: 'Dalam Inspeksi',
        damaged: 'Rusak',
        disposed: 'Dimusnahkan',
        lost: 'Hilang',
    };
    const conditions: Record<string, string> = {
        new: 'Baru',
        good: 'Baik',
        fair: 'Cukup',
        poor: 'Buruk',
    };
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
                <div>
                    <Link href="/apd/items" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Inventori
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-white">{item.item_number}{item.serial_number ? ` — ${item.serial_number}` : ''}</h2>
                </div>
            }
        >
            <Head title={`APD ${item.item_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-2">
                        {can.update && <SecondaryButton size="sm" href={`/apd/items/${item.id}/edit`}>Edit</SecondaryButton>}
                        {can.issue && item.status === 'in_stock' && <PrimaryButton size="sm" href={`/apd/issuances/create?apd_item_id=${item.id}`}>Issue</PrimaryButton>}
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-2 bg-white shadow-sm rounded-lg p-6 dark:bg-gray-900">
                            <h3 className="font-semibold text-lg mb-4 text-slate-800 dark:text-white">Detail Item</h3>
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div><dt className="text-gray-500">Katalog</dt><dd className="text-slate-800 dark:text-slate-100">{item.catalog?.name || '-'} ({item.catalog?.catalog_code || '-'})</dd></div>
                                <div><dt className="text-gray-500">Tipe</dt><dd className="text-slate-800 dark:text-slate-100">{item.track_type === 'serial' ? 'Per-Serial' : 'Per-Batch'}</dd></div>
                                <div><dt className="text-gray-500">Serial</dt><dd className="text-slate-800 dark:text-slate-100">{item.serial_number || '-'}</dd></div>
                                <div><dt className="text-gray-500">Jumlah</dt><dd className="text-slate-800 dark:text-slate-100">{item.quantity}</dd></div>
                                <div><dt className="text-gray-500">Biaya Satuan</dt><dd className="text-slate-800 dark:text-slate-100">{item.unit_cost ?? '-'}</dd></div>
                                <div><dt className="text-gray-500">Status</dt><dd><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColor[item.status] || 'bg-gray-100 text-gray-800'}`}>{statuses[item.status] || item.status}</span></dd></div>
                                <div><dt className="text-gray-500">Kondisi</dt><dd className="text-slate-800 dark:text-slate-100">{conditions[item.condition] || item.condition}</dd></div>
                                <div><dt className="text-gray-500">Site</dt><dd className="text-slate-800 dark:text-slate-100">{item.site.name}</dd></div>
                                <div><dt className="text-gray-500">Area</dt><dd className="text-slate-800 dark:text-slate-100">{item.area?.name || '-'}</dd></div>
                                <div><dt className="text-gray-500">Department</dt><dd className="text-slate-800 dark:text-slate-100">{item.department?.name || '-'}</dd></div>
                                <div><dt className="text-gray-500">Lokasi Simpan</dt><dd className="text-slate-800 dark:text-slate-100">{item.storage_location || '-'}</dd></div>
                                <div><dt className="text-gray-500">Tgl Produksi</dt><dd className="text-slate-800 dark:text-slate-100">{item.manufacture_date || '-'}</dd></div>
                                <div><dt className="text-gray-500">Tgl Pembelian</dt><dd className="text-slate-800 dark:text-slate-100">{item.purchase_date || '-'}</dd></div>
                                <div><dt className="text-gray-500">Tgl Terima</dt><dd className="text-slate-800 dark:text-slate-100">{item.received_date || '-'}</dd></div>
                                <div><dt className="text-gray-500">Kedaluwarsa</dt><dd className={item.expiry_date ? 'text-red-600 font-semibold' : 'text-slate-800 dark:text-slate-100'}>{item.expiry_date || '-'}</dd></div>
                                <div><dt className="text-gray-500">Inspeksi Berikutnya</dt><dd className="text-slate-800 dark:text-slate-100">{item.next_inspection_date || '-'}</dd></div>
                            </dl>
                            {item.notes && (
                                <div className="mt-4"><dt className="text-gray-500 text-sm">Catatan</dt><dd className="text-slate-800 dark:text-slate-100 text-sm mt-1">{item.notes}</dd></div>
                            )}
                        </div>

                        <div className="space-y-6">
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
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
