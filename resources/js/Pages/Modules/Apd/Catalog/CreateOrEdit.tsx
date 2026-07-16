import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

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
}

export default function CreateOrEdit({ catalog, categories, trackTypes, protectionLevels }: PageProps<{
    catalog?: Catalog;
    categories: Record<string, string>;
    trackTypes: Record<string, string>;
    protectionLevels: Record<string, string>;
}>) {
    const isEditing = !!catalog;

    const { data, setData, post, put, processing, errors } = useForm({
        name: catalog?.name || '',
        category: catalog?.category || '',
        track_type: catalog?.track_type || 'serial',
        sku: catalog?.sku || '',
        manufacturer: catalog?.manufacturer || '',
        model: catalog?.model || '',
        standard: catalog?.standard || '',
        size: catalog?.size || '',
        protection_level: catalog?.protection_level || '',
        default_lifespan_months: catalog?.default_lifespan_months ?? '' as any,
        inspection_interval_days: catalog?.inspection_interval_days ?? '' as any,
        default_unit_cost: catalog?.default_unit_cost ?? '' as any,
        min_stock: catalog?.min_stock ?? 0,
        reorder_point: catalog?.reorder_point ?? 0,
        is_active: catalog?.is_active ?? true,
        description: catalog?.description || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (isEditing) {
            put(`/apd/catalogs/${catalog!.id}`);
        } else {
            post('/apd/catalogs');
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/catalogs" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Katalog
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                        {isEditing ? `Edit Katalog ${catalog!.catalog_code}` : 'Tambah Katalog APD'}
                    </h2>
                </div>
            }
        >
            <Head title={isEditing ? 'Edit Katalog APD' : 'Tambah Katalog APD'} />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-900">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {isEditing && (
                                    <div>
                                        <InputLabel value="Kode Katalog" />
                                        <p className="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">{catalog!.catalog_code}</p>
                                    </div>
                                )}

                                <div className={isEditing ? '' : 'md:col-span-2'}>
                                    <InputLabel htmlFor="name" value="Nama APD *" />
                                    <TextInput id="name" type="text" className="mt-1 block w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="category" value="Kategori *" />
                                    <select id="category" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.category} onChange={(e) => setData('category', e.target.value)} required>
                                        <option value="">Pilih Kategori</option>
                                        {Object.entries(categories).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                    </select>
                                    <InputError message={errors.category} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="track_type" value="Tipe Pelacakan *" />
                                    <select id="track_type" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.track_type} onChange={(e) => setData('track_type', e.target.value)} required>
                                        {Object.entries(trackTypes).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                    </select>
                                    <InputError message={errors.track_type} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="sku" value="SKU" />
                                    <TextInput id="sku" type="text" className="mt-1 block w-full" value={data.sku} onChange={(e) => setData('sku', e.target.value)} />
                                    <InputError message={errors.sku} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="manufacturer" value="Manufacturer" />
                                    <TextInput id="manufacturer" type="text" className="mt-1 block w-full" value={data.manufacturer} onChange={(e) => setData('manufacturer', e.target.value)} />
                                    <InputError message={errors.manufacturer} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="model" value="Model" />
                                    <TextInput id="model" type="text" className="mt-1 block w-full" value={data.model} onChange={(e) => setData('model', e.target.value)} />
                                    <InputError message={errors.model} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="standard" value="Standard (SNI/EN/ANSI)" />
                                    <TextInput id="standard" type="text" className="mt-1 block w-full" value={data.standard} onChange={(e) => setData('standard', e.target.value)} />
                                    <InputError message={errors.standard} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="size" value="Ukuran" />
                                    <TextInput id="size" type="text" className="mt-1 block w-full" value={data.size} onChange={(e) => setData('size', e.target.value)} />
                                    <InputError message={errors.size} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="protection_level" value="Level Perlindungan" />
                                    <select id="protection_level" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" value={data.protection_level} onChange={(e) => setData('protection_level', e.target.value)}>
                                        <option value="">Pilih Level</option>
                                        {Object.entries(protectionLevels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                                    </select>
                                    <InputError message={errors.protection_level} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="default_lifespan_months" value="Masa Pakai (bulan)" />
                                    <TextInput id="default_lifespan_months" type="number" className="mt-1 block w-full" value={data.default_lifespan_months} onChange={(e) => setData('default_lifespan_months', e.target.value === '' ? '' as any : Number(e.target.value))} />
                                    <InputError message={errors.default_lifespan_months} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="inspection_interval_days" value="Interval Inspeksi (hari)" />
                                    <TextInput id="inspection_interval_days" type="number" className="mt-1 block w-full" value={data.inspection_interval_days} onChange={(e) => setData('inspection_interval_days', e.target.value === '' ? '' as any : Number(e.target.value))} />
                                    <InputError message={errors.inspection_interval_days} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="default_unit_cost" value="Biaya Satuan" />
                                    <TextInput id="default_unit_cost" type="number" step="0.01" className="mt-1 block w-full" value={data.default_unit_cost} onChange={(e) => setData('default_unit_cost', e.target.value === '' ? '' as any : Number(e.target.value))} />
                                    <InputError message={errors.default_unit_cost} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="min_stock" value="Stok Minimum" />
                                    <TextInput id="min_stock" type="number" className="mt-1 block w-full" value={data.min_stock} onChange={(e) => setData('min_stock', Number(e.target.value))} />
                                    <InputError message={errors.min_stock} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="reorder_point" value="Titik Pemesanan" />
                                    <TextInput id="reorder_point" type="number" className="mt-1 block w-full" value={data.reorder_point} onChange={(e) => setData('reorder_point', Number(e.target.value))} />
                                    <InputError message={errors.reorder_point} className="mt-2" />
                                </div>

                                <div className="flex items-center">
                                    <input id="is_active" type="checkbox" className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                                    <label htmlFor="is_active" className="ml-2 text-sm text-gray-700 dark:text-gray-200">Katalog Aktif</label>
                                </div>
                            </div>

                            <div className="mt-6">
                                <InputLabel htmlFor="description" value="Deskripsi" />
                                <textarea id="description" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                                <InputError message={errors.description} className="mt-2" />
                            </div>

                            <div className="mt-6 flex items-center justify-end gap-4">
                                <Link href="/apd/catalogs" className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">
                                    Batal
                                </Link>
                                <PrimaryButton disabled={processing}>{isEditing ? 'Update Katalog' : 'Simpan Katalog'}</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
