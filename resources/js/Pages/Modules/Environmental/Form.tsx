import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, EnvironmentalRecord, Site, Area, EnvironmentalType } from '@/types';
import { useState } from 'react';
import TypeFields from '@/Components/Environmental/TypeFields';

interface FormData {
    type: string;
    title: string;
    description: string;
    site_id: string | number;
    area_id: string | number;
    occurred_at: string;
    measured_value: string | number;
    unit: string;
    limit_value: string | number;
    waste_type: string;
    quantity: string | number;
    disposal_method: string;
    material: string;
    volume: string | number;
    containment: string;
    parameter: string;
    location: string;
}

interface FormProps extends PageProps {
    record?: EnvironmentalRecord | null;
    sites: Site[];
    areas: (Area & { site_id: number })[];
    types: Record<string, string>;
    statuses?: Record<string, string>;
}

const inputClass =
    'w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const errClass = 'mt-1 text-sm text-red-600 dark:text-red-400';

const unitByType: Record<string, string[]> = {
    waste: ['kg', 'liter', 'm³'],
    spill: ['liter', 'm³', 'barrel'],
    emission: ['mg/m³', 'ppm', 'µg/m³'],
    noise: ['dB'],
    water_monitoring: ['mg/L', 'pH', 'µS/cm'],
    other: ['kg', 'liter', 'm³', 'mg/m³', 'mg/L', 'ppm', 'dB'],
};

export default function Form({ auth, record, sites, areas, types }: FormProps) {
    const isEdit = !!record;
    const [type, setType] = useState<EnvironmentalType | ''>(record?.type ?? '');

    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        type: record?.type ?? '',
        title: record?.title ?? '',
        description: record?.description ?? '',
        site_id: record?.site_id ?? '',
        area_id: record?.area_id ?? '',
        occurred_at: record?.occurred_at ? toLocalInput(record.occurred_at) : '',
        measured_value: record?.measured_value ?? '',
        unit: record?.unit ?? '',
        limit_value: record?.limit_value ?? '',
        waste_type: record?.waste_type ?? '',
        quantity: record?.quantity ?? '',
        disposal_method: record?.disposal_method ?? '',
        material: record?.material ?? '',
        volume: record?.volume ?? '',
        containment: record?.containment ?? '',
        parameter: record?.parameter ?? '',
        location: record?.location ?? '',
    });

    function toLocalInput(dt: string): string {
        const d = new Date(dt);
        const off = d.getTimezoneOffset();
        return new Date(d.getTime() - off * 60000).toISOString().slice(0, 16);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (data.occurred_at) {
            setData('occurred_at', new Date(data.occurred_at as string).toISOString());
        }
        if (isEdit && record) {
            put(route('environment.records.update', record.id));
        } else {
            post(route('environment.records.store'));
        }
    }

    const areaOptions = areas.filter((a) => !data.site_id || a.site_id === Number(data.site_id));
    const typeEntries = Object.entries(types);

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Catatan Lingkungan' : 'Buat Catatan Lingkungan'}</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{isEdit ? 'Perbarui data catatan lingkungan' : 'Isi data catatan lingkungan dengan lengkap'}</p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Catatan Lingkungan' : 'Buat Catatan Lingkungan'} />
            <div className="py-6">
                <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Informasi Umum */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">INFORMASI UMUM</h3>
                            <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div>
                                    <span className={labelClass}>Nomor Catatan</span>
                                    <input disabled value={isEdit ? record!.record_number : 'Auto-generated (ENV-XXXX)'} className={`${inputClass} font-mono opacity-70`} />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Nomor akan dibuat otomatis saat simpan.</p>
                                </div>
                                <div>
                                    <span className={labelClass}>Tipe Catatan <span className="text-red-500">*</span></span>
                                    <select
                                        value={data.type as string}
                                        onChange={(e) => { setType(e.target.value as EnvironmentalType | ''); setData('type', e.target.value); }}
                                        disabled={isEdit}
                                        className={`${inputClass} ${errors.type ? 'border-red-500' : ''}`}
                                        required
                                    >
                                        <option value="">— Pilih Tipe —</option>
                                        {typeEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                    {errors.type && <p className={errClass}>{errors.type as string}</p>}
                                </div>
                                <div>
                                    <span className={labelClass}>Judul <span className="text-red-500">*</span></span>
                                    <input type="text" value={data.title as string} onChange={(e) => setData('title', e.target.value)} maxLength={255} className={`${inputClass} ${errors.title ? 'border-red-500' : ''}`} placeholder="Masukkan judul catatan..." required />
                                    {errors.title && <p className={errClass}>{errors.title as string}</p>}
                                </div>
                                <div>
                                    <span className={labelClass}>Deskripsi <span className="text-red-500">*</span></span>
                                    <textarea value={data.description as string} onChange={(e) => setData('description', e.target.value)} rows={3} className={`${inputClass} ${errors.description ? 'border-red-500' : ''}`} placeholder="Jelaskan detail pengamatan lingkungan..." required />
                                    {errors.description && <p className={errClass}>{errors.description as string}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Lokasi */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">LOKASI</h3>
                            <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span className={labelClass}>Site <span className="text-red-500">*</span></span>
                                        <select value={data.site_id as string} onChange={(e) => { setData('site_id', e.target.value); setData('area_id', ''); }} className={`${inputClass} ${errors.site_id ? 'border-red-500' : ''}`} required>
                                            <option value="">— Pilih Site —</option>
                                            {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                        </select>
                                        {errors.site_id && <p className={errClass}>{errors.site_id as string}</p>}
                                    </div>
                                    <div>
                                        <span className={labelClass}>Area</span>
                                        <select value={data.area_id as string} onChange={(e) => setData('area_id', e.target.value)} className={inputClass} disabled={areaOptions.length === 0}>
                                            <option value="">— Pilih Area —</option>
                                            {areaOptions.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <span className={labelClass}>Tanggal Kejadian</span>
                                    <input type="datetime-local" value={data.occurred_at as string} onChange={(e) => setData('occurred_at', e.target.value)} className={inputClass} />
                                </div>
                            </div>
                        </div>

                        {/* Detail Pengukuran */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">DETAIL PENGUKURAN</h3>
                            <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <TypeFields
                                    type={type}
                                    data={data}
                                    setData={setData}
                                    errors={errors}
                                    unitOptions={type ? unitByType[type] ?? unitByType.other : unitByType.other}
                                />
                            </div>
                        </div>

                        {/* Action Bar */}
                        <div className="sticky bottom-0 flex items-center justify-between gap-2 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                            <Link href={route('environment.records.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">← Batal</Link>
                            <button type="submit" disabled={processing} className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                                {isEdit ? 'Simpan' : 'Simpan'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
