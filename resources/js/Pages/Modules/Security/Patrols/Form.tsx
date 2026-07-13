import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Option, Patrol } from './types';

interface Props extends PageProps {
    patrol: Patrol | null;
    sites: Option[];
    areas: Option[];
    officers: Option[];
}

const localDateTime = (value?: string) => value
    ? new Date(value).toISOString().slice(0, 16)
    : '';

export default function Form({ patrol, sites, areas, officers }: Props) {
    const assignedId = typeof patrol?.assigned_to === 'object'
        ? patrol.assigned_to.id
        : patrol?.assigned_to;
    const { data, setData, post, put, processing, errors } = useForm({
        title: patrol?.title ?? '',
        description: patrol?.description ?? '',
        site_id: patrol?.site_id ? String(patrol.site_id) : '',
        area_id: patrol?.area_id ? String(patrol.area_id) : '',
        scheduled_at: localDateTime(patrol?.scheduled_at),
        assigned_to: assignedId ? String(assignedId) : '',
        notes: patrol?.notes ?? '',
        checkpoints: patrol?.results?.map((item) => ({ checkpoint: item.checkpoint })) ?? [{ checkpoint: '' }],
    });
    const fieldErrors = errors as Record<string, string>;
    const filteredAreas = areas.filter((area) => String(area.site_id) === data.site_id);
    const filteredOfficers = officers.filter((officer) => String(officer.site_id) === data.site_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patrol
            ? put(route('security.patrols.update', patrol.id))
            : post(route('security.patrols.store'));
    };

    const setCheckpoint = (index: number, checkpoint: string) => {
        setData('checkpoints', data.checkpoints.map((item, itemIndex) =>
            itemIndex === index ? { checkpoint } : item));
    };

    const addCheckpoint = () => setData('checkpoints', [...data.checkpoints, { checkpoint: '' }]);
    const removeCheckpoint = (index: number) => {
        if (data.checkpoints.length > 1) {
            setData('checkpoints', data.checkpoints.filter((_, itemIndex) => itemIndex !== index));
        }
    };

    const inputClass = 'mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white';

    return (
        <AuthenticatedLayout>
            <Head title={patrol ? 'Edit Patroli' : 'Jadwalkan Patroli'} />
            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <header className="mb-6">
                        <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">{patrol ? 'Edit Jadwal Patroli' : 'Jadwalkan Patroli Keamanan'}</h1>
                        <p className="text-sm text-slate-500">Nomor SPL dibuat otomatis saat disimpan.</p>
                    </header>

                    <form onSubmit={submit} className="space-y-6">
                        <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                            <h2 className="mb-5 font-semibold text-slate-900 dark:text-white">Informasi Patroli</h2>
                            <div className="grid gap-5 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="title" value="Rute / Judul Patroli *" />
                                    <TextInput id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} className="mt-1 block w-full" placeholder="Contoh: Rute Malam — Gerbang ke Gudang" required />
                                    <InputError message={errors.title} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="site_id" value="Site *" />
                                    <select id="site_id" value={data.site_id} onChange={(e) => { setData('site_id', e.target.value); setData('area_id', ''); }} className={inputClass} required>
                                        <option value="">Pilih site</option>
                                        {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                                    </select>
                                    <InputError message={errors.site_id} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="area_id" value="Area" />
                                    <select id="area_id" value={data.area_id} onChange={(e) => setData('area_id', e.target.value)} className={inputClass} disabled={!data.site_id}>
                                        <option value="">Semua area / tidak spesifik</option>
                                        {filteredAreas.map((area) => <option key={area.id} value={area.id}>{area.name}</option>)}
                                    </select>
                                    <InputError message={errors.area_id} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="assigned_to" value="Petugas *" />
                                    <select id="assigned_to" value={data.assigned_to} onChange={(e) => setData('assigned_to', e.target.value)} className={inputClass} disabled={!data.site_id} required>
                                        <option value="">Pilih petugas Security pada site</option>
                                        {filteredOfficers.map((officer) => <option key={officer.id} value={officer.id}>{officer.name} — {officer.email}</option>)}
                                    </select>
                                    <InputError message={errors.assigned_to} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="scheduled_at" value="Jadwal *" />
                                    <TextInput id="scheduled_at" type="datetime-local" value={data.scheduled_at} onChange={(e) => setData('scheduled_at', e.target.value)} className="mt-1 block w-full" required />
                                    <InputError message={errors.scheduled_at} className="mt-1" />
                                </div>
                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="description" value="Deskripsi" />
                                    <textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} className={inputClass} />
                                    <InputError message={errors.description} className="mt-1" />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                            <div className="mb-4 flex items-center justify-between">
                                <div><h2 className="font-semibold text-slate-900 dark:text-white">Checkpoint *</h2><p className="text-xs text-slate-500">Minimal satu, maksimal 50 checkpoint.</p></div>
                                <button type="button" onClick={addCheckpoint} className="rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-200">+ Tambah</button>
                            </div>
                            <div className="space-y-3">
                                {data.checkpoints.map((item, index) => (
                                    <div key={index} className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                                        <div className="flex gap-3">
                                            <div className="flex-1"><InputLabel htmlFor={`checkpoint-${index}`} value={`Checkpoint ${index + 1}`} /><TextInput id={`checkpoint-${index}`} value={item.checkpoint} onChange={(e) => setCheckpoint(index, e.target.value)} className="mt-1 block w-full" required /><InputError message={fieldErrors[`checkpoints.${index}.checkpoint`]} className="mt-1" /></div>
                                            <button type="button" onClick={() => removeCheckpoint(index)} disabled={data.checkpoints.length === 1} className="self-end rounded-lg px-3 py-2 text-sm text-red-600 disabled:opacity-30">Hapus</button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.checkpoints} className="mt-2" />
                        </section>

                        <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                            <InputLabel htmlFor="notes" value="Catatan Patroli" />
                            <textarea id="notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows={3} className={inputClass} />
                            <InputError message={errors.notes} className="mt-1" />
                        </section>

                        <div className="flex items-center justify-between">
                            <Link href={patrol ? route('security.patrols.show', patrol.id) : route('security.patrols.index')} className="text-sm text-slate-600 dark:text-slate-300">← Batal</Link>
                            <PrimaryButton disabled={processing}>{patrol ? 'Perbarui Jadwal' : 'Simpan Jadwal'}</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
