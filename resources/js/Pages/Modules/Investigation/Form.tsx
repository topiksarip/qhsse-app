import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Incident = { id: number; incident_number: string; title: string };

type Props = {
    item: {
        id: number; investigation_number: string; title: string; status: string;
        incident_id: number; root_cause: string | null; recommendations: string | null;
        five_whys: Array<{ level: number; question: string; answer: string; is_root_cause: boolean }> | null;
        fishbone: Array<{ category: string; causes: string[] }> | null;
    } | null;
    incidents: Incident[];
};

const fishboneCategories = ['Man', 'Method', 'Machine', 'Material', 'Environment', 'Management'];

export default function Form({ item, incidents }: PageProps<Props>) {
    const isEdit = item !== null;
    const isEditable = !item || item.status === 'draft' || item.status === 'in_progress';

    const { data, setData, post, put, processing, errors } = useForm({
        incident_id: item?.incident_id ?? '',
        title: item?.title ?? '',
        root_cause: item?.root_cause ?? '',
        recommendations: item?.recommendations ?? '',
        action: 'draft' as 'draft' | 'start',
    });

    function submit(e: FormEvent, action: 'draft' | 'start') {
        e.preventDefault();
        setData('action', action);
        if (isEdit) {
            put(route('investigation.reports.update', item!.id));
        } else {
            post(route('investigation.reports.store'));
        }
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Investigasi' : 'Buat Investigasi'}</h2>}>
            <Head title={isEdit ? 'Edit Investigasi' : 'Buat Investigasi'} />
            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Informasi Umum */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Umum</h3>
                        <div className="grid gap-4">
                            {isEdit && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Investigasi</label>
                                    <input type="text" value={item!.investigation_number} disabled className="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400" />
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Insiden Terkait *</label>
                                <select value={data.incident_id} onChange={(e) => setData('incident_id', Number(e.target.value))} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Insiden</option>
                                    {incidents.map((i) => <option key={i.id} value={i.id}>{i.incident_number} — {i.title}</option>)}
                                </select>
                                {errors.incident_id && <p className="mt-1 text-sm text-red-600">{errors.incident_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul *</label>
                                <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                                {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                            </div>
                        </div>
                    </div>

                    {/* Root Cause Analysis */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Root Cause Analysis</h3>
                        <div className="grid gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Root Cause</label>
                                <textarea value={data.root_cause} onChange={(e) => setData('root_cause', e.target.value)} disabled={!isEditable} rows={4} placeholder="Ringkasan akar penyebab..." className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Rekomendasi</label>
                                <textarea value={data.recommendations} onChange={(e) => setData('recommendations', e.target.value)} disabled={!isEditable} rows={3} placeholder="Rekomendasi tindakan korektif..." className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            </div>
                        </div>
                        {isEdit && item?.five_whys && (
                            <div className="mt-6">
                                <h4 className="text-sm font-semibold text-gray-600 dark:text-gray-400">5-Why Analysis</h4>
                                <div className="mt-2 space-y-2">
                                    {item.five_whys.map((w, i) => (
                                        <div key={i} className="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                            <p className="text-sm font-medium text-gray-700 dark:text-gray-300">Why {w.level}: {w.question}</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">→ {w.answer}</p>
                                            {w.is_root_cause && <span className="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Root Cause</span>}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                        {isEdit && item?.fishbone && (
                            <div className="mt-4">
                                <h4 className="text-sm font-semibold text-gray-600 dark:text-gray-400">Fishbone (Ishikawa)</h4>
                                <div className="mt-2 grid gap-2 md:grid-cols-2">
                                    {item.fishbone.map((f, i) => (
                                        <div key={i} className="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                            <p className="text-sm font-medium text-gray-700 dark:text-gray-300">{f.category}</p>
                                            {f.causes.length > 0 ? (
                                                <ul className="mt-1 text-sm text-gray-600 dark:text-gray-400">{f.causes.map((c, j) => <li key={j}>• {c}</li>)}</ul>
                                            ) : <p className="text-xs text-gray-400">No causes</p>}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Buttons */}
                    <div className="flex justify-between">
                        <Link href={route('investigation.reports.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</Link>
                        {isEditable && (
                            <div className="flex gap-2">
                                <button type="button" onClick={(e) => submit(e, 'draft')} disabled={processing} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Simpan Draft</button>
                                <button type="button" onClick={(e) => submit(e, 'start')} disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Mulai Investigasi</button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
