import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect';

type Template = { id: number; name: string; items: { id: number; question: string; type: string }[] };
type Site = { id: number; name: string };
type Area = { id: number; name: string; site_id: number };
type UserT = { id: number; name: string };

export default function Form({ item, templates, sites, areas, users }: PageProps<{
    item: null; templates: Template[]; sites: Site[]; areas: Area[]; users: UserT[];
}>) {
    const { data, setData, post, processing, errors } = useForm({
        inspection_template_id: '' as string | number,
        site_id: '' as string | number,
        area_id: '' as string | number,
        inspector_id: '' as string | number,
        scheduled_at: '',
        units: [] as string[],
    });

    const [available, setAvailable] = useState<string[]>([]);
    const [draft, setDraft] = useState('');

    function addFromText(text: string) {
        const lines = text
            .split(/[\n,]/)
            .map((l) => l.trim())
            .filter((l) => l.length > 0);
        if (lines.length === 0) return;
        setAvailable((prev) => {
            const merged = [...prev];
            for (const l of lines) if (!merged.includes(l)) merged.push(l);
            return merged;
        });
        setDraft('');
    }

    function removeAvailable(unit: string) {
        setAvailable((prev) => prev.filter((u) => u !== unit));
        setData('units', data.units.filter((u) => u !== unit));
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        // If nothing selected but list built, default to all available.
        if (data.units.length === 0 && available.length > 0) {
            setData('units', available);
        }
        post(route('inspection.checklists.store'));
    }

    const options = available.map((u) => ({ value: u, label: u }));

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Buat Inspeksi</h2>}>
            <Head title="Buat Inspeksi" />
            <div className="py-12"><div className="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <form onSubmit={submit} className="grid gap-4">
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Template *</label>
                            <select value={data.inspection_template_id} onChange={(e) => setData('inspection_template_id', Number(e.target.value))} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Pilih Template</option>{templates.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
                            </select>{errors.inspection_template_id && <p className="mt-1 text-sm text-red-600">{errors.inspection_template_id}</p>}
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Site *</label>
                                <select value={data.site_id} onChange={(e) => setData('site_id', Number(e.target.value))} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Site</option>{sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>{errors.site_id && <p className="mt-1 text-sm text-red-600">{errors.site_id}</p>}
                            </div>
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Area</label>
                                <select value={data.area_id} onChange={(e) => setData('area_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Area</option>{areas.filter((a) => !data.site_id || a.site_id === Number(data.site_id)).map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Inspector *</label>
                                <select value={data.inspector_id} onChange={(e) => setData('inspector_id', Number(e.target.value))} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Inspector</option>{users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                                </select>{errors.inspector_id && <p className="mt-1 text-sm text-red-600">{errors.inspector_id}</p>}
                            </div>
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Jadwal *</label>
                                <input type="date" value={data.scheduled_at} onChange={(e) => setData('scheduled_at', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                                {errors.scheduled_at && <p className="mt-1 text-sm text-red-600">{errors.scheduled_at}</p>}
                            </div>
                        </div>

                        <div className="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                            <h3 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Daftar Unit</h3>
                            <p className="mb-2 text-xs text-gray-500">Tempel daftar unit (satu per baris atau pisahkan dengan koma), lalu pilih unit yang akan diinspeksi.</p>
                            <div className="flex gap-2">
                                <textarea
                                    value={draft}
                                    onChange={(e) => setDraft(e.target.value)}
                                    placeholder={'Sling-01\nSling-02\nSling-03'}
                                    rows={3}
                                    className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                />
                                <button type="button" onClick={() => addFromText(draft)} className="self-start rounded-md bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Tambah</button>
                            </div>
                            {available.length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {available.map((u) => (
                                        <span key={u} className="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {u}
                                            <button type="button" onClick={() => removeAvailable(u)} className="text-red-500">×</button>
                                        </span>
                                    ))}
                                </div>
                            )}
                            {available.length > 0 && (
                                <div className="mt-3">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit terpilih untuk inspeksi *</label>
                                    <SearchableMultiSelect
                                        options={options}
                                        value={data.units}
                                        onChange={(next) => setData('units', next)}
                                        placeholder="Pilih unit..."
                                    />
                                    {errors.units && <p className="mt-1 text-sm text-red-600">{errors.units}</p>}
                                </div>
                            )}
                        </div>

                        <div className="flex justify-between">
                            <Link href={route('inspection.checklists.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</Link>
                            <button type="submit" disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
                        </div>
                    </form>
                </div>
            </div></div>
        </AuthenticatedLayout>
    );
}
