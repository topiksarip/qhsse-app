import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

interface CatalogOption {
    id: number;
    name: string;
    catalog_code: string;
    track_type: string;
}

export default function CreateOrReceive({ catalogs, sites, areas, departments, conditions }: PageProps<{
    catalogs: CatalogOption[];
    sites: Array<{ id: number; name: string }>;
    areas: Array<{ id: number; name: string; site_id: number }>;
    departments: Array<{ id: number; name: string; site_id: number }>;
    conditions: Record<string, string>;
}>) {
    const { data, setData, post, processing, errors } = useForm({
        catalog_id: '' as any,
        track_type: 'serial',
        serial_number: '',
        quantity: 1,
        unit_cost: '' as any,
        site_id: '' as any,
        area_id: '' as any,
        department_id: '' as any,
        storage_location: '',
        condition: 'new',
        manufacture_date: '',
        purchase_date: '',
        received_date: '',
        expiry_date: '',
        notes: '',
    });

    const selectedCatalog = catalogs.find(c => c.id === Number(data.catalog_id));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/apd/items');
    };

    const filteredAreas = areas.filter(a => a.site_id === Number(data.site_id));
    const filteredDepartments = departments.filter(d => d.site_id === Number(data.site_id));

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/items" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Inventori
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-white">Terima Stok APD</h2>
                </div>
            }
        >
            <Head title="Terima Stok APD" />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-900">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel htmlFor="catalog_id" value="Katalog APD *" />
                                    <select id="catalog_id" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.catalog_id} onChange={(e) => {
                                        const cat = catalogs.find(c => c.id === Number(e.target.value));
                                        setData('catalog_id', e.target.value);
                                        if (cat) setData('track_type', cat.track_type);
                                    }} required>
                                        <option value="">Pilih Katalog</option>
                                        {catalogs.map(c => <option key={c.id} value={c.id}>{c.name} ({c.catalog_code})</option>)}
                                    </select>
                                    <InputError message={errors.catalog_id} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="track_type" value="Tipe Pelacakan" />
                                    <p className="mt-2 text-sm text-gray-700 dark:text-gray-200">{data.track_type === 'serial' ? 'Per-Serial (1 unit)' : 'Per-Batch (kuantitas)'}</p>
                                </div>

                                {data.track_type === 'serial' ? (
                                    <div>
                                        <InputLabel htmlFor="serial_number" value="Nomor Serial *" />
                                        <TextInput id="serial_number" type="text" className="mt-1 block w-full" value={data.serial_number} onChange={(e) => setData('serial_number', e.target.value)} />
                                        <InputError message={errors.serial_number} className="mt-2" />
                                    </div>
                                ) : (
                                    <div>
                                        <InputLabel htmlFor="quantity" value="Jumlah *" />
                                        <TextInput id="quantity" type="number" className="mt-1 block w-full" value={data.quantity} onChange={(e) => setData('quantity', Number(e.target.value))} />
                                        <InputError message={errors.quantity} className="mt-2" />
                                    </div>
                                )}

                                <div>
                                    <InputLabel htmlFor="unit_cost" value="Biaya Satuan" />
                                    <TextInput id="unit_cost" type="number" step="0.01" className="mt-1 block w-full" value={data.unit_cost} onChange={(e) => setData('unit_cost', e.target.value === '' ? '' as any : Number(e.target.value))} />
                                    <InputError message={errors.unit_cost} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="site_id" value="Site *" />
                                    <select id="site_id" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.site_id} onChange={(e) => { setData('site_id', e.target.value); setData('area_id', ''); setData('department_id', ''); }} required>
                                        <option value="">Pilih Site</option>
                                        {sites.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
                                    </select>
                                    <InputError message={errors.site_id} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="area_id" value="Area" />
                                    <select id="area_id" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.area_id} onChange={(e) => setData('area_id', e.target.value)} disabled={!data.site_id}>
                                        <option value="">Pilih Area</option>
                                        {filteredAreas.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                                    </select>
                                </div>

                                <div>
                                    <InputLabel htmlFor="department_id" value="Department" />
                                    <select id="department_id" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.department_id} onChange={(e) => setData('department_id', e.target.value)}>
                                        <option value="">Pilih Department</option>
                                        {filteredDepartments.map(d => <option key={d.id} value={d.id}>{d.name}</option>)}
                                    </select>
                                </div>

                                <div>
                                    <InputLabel htmlFor="storage_location" value="Lokasi Simpan" />
                                    <TextInput id="storage_location" type="text" className="mt-1 block w-full" value={data.storage_location} onChange={(e) => setData('storage_location', e.target.value)} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="condition" value="Kondisi Awal" />
                                    <select id="condition" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.condition} onChange={(e) => setData('condition', e.target.value)}>
                                        {Object.entries(conditions).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                    </select>
                                </div>

                                <div>
                                    <InputLabel htmlFor="manufacture_date" value="Tgl Produksi" />
                                    <TextInput id="manufacture_date" type="date" className="mt-1 block w-full" value={data.manufacture_date} onChange={(e) => setData('manufacture_date', e.target.value)} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="purchase_date" value="Tgl Pembelian" />
                                    <TextInput id="purchase_date" type="date" className="mt-1 block w-full" value={data.purchase_date} onChange={(e) => setData('purchase_date', e.target.value)} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="received_date" value="Tgl Terima" />
                                    <TextInput id="received_date" type="date" className="mt-1 block w-full" value={data.received_date} onChange={(e) => setData('received_date', e.target.value)} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="expiry_date" value="Tgl Kedaluwarsa" />
                                    <TextInput id="expiry_date" type="date" className="mt-1 block w-full" value={data.expiry_date} onChange={(e) => setData('expiry_date', e.target.value)} />
                                    <InputError message={errors.expiry_date} className="mt-2" />
                                </div>
                            </div>

                            <div className="mt-6">
                                <InputLabel htmlFor="notes" value="Catatan" />
                                <textarea id="notes" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                            </div>

                            <div className="mt-6 flex items-center justify-end gap-4">
                                <Link href="/apd/items" className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">
                                    Batal
                                </Link>
                                <PrimaryButton disabled={processing}>Terima ke Stok</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
