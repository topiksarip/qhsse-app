import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler, useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

interface Option { id: number; item_number: string; status: string; condition: string; catalog?: { name: string } }

type Props = {
    inspection: null;
    items: Option[];
    inspectionTypes: Record<string, string>;
    results: Record<string, string>;
    conditions: Record<string, string>;
};

export default function Form({ items, inspectionTypes, results, conditions }: PageProps<Props>) {
    const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
    const initialItemId = urlParams.get('apd_item_id') || '';

    const { data, setData, post, processing, errors } = useForm({
        apd_item_id: initialItemId,
        inspection_type: 'manual',
        inspection_date: new Date().toISOString().slice(0, 10),
        result: 'layak',
        condition: '',
        next_inspection_date: '',
        notes: '',
        photos: [] as File[],
    });

    const [selectedPhotos, setSelectedPhotos] = useState<File[]>([]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/apd/inspections', { forceFormData: true });
    };

    const onPhotos = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files ? Array.from(e.target.files) : [];
        setSelectedPhotos(files);
        setData('photos', files);
    };

    const inputCls =
        'mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/inspections" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Inspeksi
                    </Link>
                    <h2 className="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Inspeksi APD Baru</h2>
                </div>
            }
        >
            <Head title="Inspeksi APD" />
            <div className="py-6">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div>
                            <InputLabel htmlFor="apd_item_id" value="Item APD" />
                            <select id="apd_item_id" className={inputCls} value={data.apd_item_id} onChange={(e) => setData('apd_item_id', e.target.value)}>
                                <option value="">— Pilih item —</option>
                                {items.map((i) => (
                                    <option key={i.id} value={i.id}>
                                        {i.item_number} — {i.catalog?.name ?? '?'} [{i.status}]
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.apd_item_id} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="inspection_type" value="Tipe Inspeksi" />
                                <select id="inspection_type" className={inputCls} value={data.inspection_type} onChange={(e) => setData('inspection_type', e.target.value)}>
                                    {Object.entries(inspectionTypes).map(([v, l]) => (
                                        <option key={v} value={v}>{l}</option>
                                    ))}
                                </select>
                                <InputError message={errors.inspection_type} />
                            </div>
                            <div>
                                <InputLabel htmlFor="inspection_date" value="Tgl Inspeksi" />
                                <TextInput id="inspection_date" type="date" className={inputCls} value={data.inspection_date} onChange={(e) => setData('inspection_date', e.target.value)} />
                                <InputError message={errors.inspection_date} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="result" value="Hasil" />
                                <select
                                    id="result"
                                    className={inputCls}
                                    value={data.result}
                                    onChange={(e) => {
                                        setData('result', e.target.value);
                                        if (e.target.value === 'tidak_layak' && !data.condition) {
                                            setData('condition', 'poor');
                                        }
                                    }}
                                >
                                    {Object.entries(results).map(([v, l]) => (
                                        <option key={v} value={v}>{l}</option>
                                    ))}
                                </select>
                                <InputError message={errors.result} />
                                {data.result === 'tidak_layak' && (
                                    <p className="mt-1 text-xs font-medium text-red-600 dark:text-red-400">Tidak layak → item akan ditandai rusak (damaged).</p>
                                )}
                            </div>
                            <div>
                                <InputLabel htmlFor="condition" value="Kondisi Item" />
                                <select id="condition" className={inputCls} value={data.condition} onChange={(e) => setData('condition', e.target.value)}>
                                    <option value="">— Pilih —</option>
                                    {Object.entries(conditions).map(([v, l]) => (
                                        <option key={v} value={v}>{l}</option>
                                    ))}
                                </select>
                                <InputError message={errors.condition} />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="next_inspection_date" value="Jadwal Inspeksi Berikutnya" />
                            <TextInput id="next_inspection_date" type="date" className={inputCls} value={data.next_inspection_date} onChange={(e) => setData('next_inspection_date', e.target.value)} />
                            <InputError message={errors.next_inspection_date} />
                        </div>

                        <div>
                            <InputLabel htmlFor="notes" value="Catatan" />
                            <textarea id="notes" className="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                            <InputError message={errors.notes} />
                        </div>

                        <div>
                            <InputLabel htmlFor="photos" value="Foto Bukti (maks 5)" />
                            <input
                                id="photos"
                                type="file"
                                accept="image/*,application/pdf"
                                multiple
                                onChange={onPhotos}
                                className="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            />
                            {selectedPhotos.length > 0 && (
                                <ul className="mt-2 list-inside list-disc text-xs text-gray-500">
                                    {selectedPhotos.map((f, idx) => (
                                        <li key={idx}>{f.name}</li>
                                    ))}
                                </ul>
                            )}
                            <InputError message={errors.photos} />
                        </div>

                        <div className="flex gap-2">
                            <PrimaryButton disabled={processing}>Simpan Inspeksi</PrimaryButton>
                            <SecondaryButton href="/apd/inspections">Batal</SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
