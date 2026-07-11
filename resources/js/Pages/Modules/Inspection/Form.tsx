import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Template = { id: number; name: string; items: { id: number; question: string; type: string }[] };
type Site = { id: number; name: string };
type Area = { id: number; name: string; site_id: number };
type UserT = { id: number; name: string };

export default function Form({ item, templates, sites, areas, users }: PageProps<{
    item: null; templates: Template[]; sites: Site[]; areas: Area[]; users: UserT[];
}>) {
    const { data, setData, post, processing, errors } = useForm({
        inspection_template_id: '' as string | number, site_id: '' as string | number, area_id: '' as string | number, inspector_id: '' as string | number, scheduled_at: '',
    });

    function submit(e: FormEvent) { e.preventDefault(); post(route('inspection.checklists.store')); }

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
