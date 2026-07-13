import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

type ImportType = { key: string; label: string; headers: string[] };
type Props = { types: ImportType[] };

export default function Import({ types }: Props) {
    const [type, setType] = useState(types[0]?.key ?? '');
    const selected = useMemo(() => types.find((item) => item.key === type), [type, types]);
    const { data, setData, post, processing, errors, reset } = useForm<{ file: File | null }>({ file: null });
    const rowErrors = Object.entries(errors).filter(([key]) => key.startsWith('rows.'));

    function submit(event: FormEvent) {
        event.preventDefault();
        if (!data.file || !type) return;
        post(route('admin.import.store', type), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('file'),
        });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Bulk Import" />
            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div>
                        <Link href={route('admin.dashboard')} className="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Admin Dashboard</Link>
                        <h1 className="mt-2 text-3xl font-bold text-slate-950 dark:text-white">Bulk Import CSV</h1>
                        <p className="mt-1 text-sm text-slate-500">Maksimal 1.000 baris. Seluruh file divalidasi sebelum data disimpan.</p>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-lg bg-white p-6 shadow-sm dark:bg-slate-800">
                        <div>
                            <label htmlFor="type" className="text-sm font-medium text-slate-700 dark:text-slate-300">Jenis data</label>
                            <select id="type" value={type} onChange={(event) => { setType(event.target.value); reset('file'); }} className="mt-2 w-full rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                                {types.map((item) => <option key={item.key} value={item.key}>{item.label}</option>)}
                            </select>
                        </div>

                        {selected && (
                            <div className="rounded-md border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="font-semibold">Kolom CSV</p>
                                        <code className="mt-1 block break-all text-xs">{selected.headers.join(',')}</code>
                                    </div>
                                    <a href={route('admin.import.template', type)} className="shrink-0 rounded-md border border-indigo-300 px-3 py-2 text-center font-semibold hover:bg-indigo-100 dark:border-indigo-700 dark:hover:bg-indigo-900">Download template</a>
                                </div>
                            </div>
                        )}

                        <div>
                            <label htmlFor="file" className="flex cursor-pointer flex-col items-center rounded-lg border-2 border-dashed border-slate-300 px-6 py-10 text-center hover:border-indigo-400 dark:border-slate-600">
                                <span className="font-medium text-slate-800 dark:text-slate-200">{data.file?.name ?? 'Pilih file CSV'}</span>
                                <span className="mt-1 text-xs text-slate-500">UTF-8, maksimal 5 MB</span>
                            </label>
                            <input id="file" type="file" accept=".csv,text/csv" className="sr-only" onChange={(event) => setData('file', event.target.files?.[0] ?? null)} />
                            {errors.file && <p className="mt-2 text-sm text-red-600">{errors.file}</p>}
                        </div>

                        {rowErrors.length > 0 && (
                            <div className="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                                <h2 className="font-semibold text-red-800 dark:text-red-200">Import dibatalkan — perbaiki semua baris berikut</h2>
                                <ul className="mt-2 max-h-56 space-y-1 overflow-y-auto text-sm text-red-700 dark:text-red-300">
                                    {rowErrors.map(([key, message]) => <li key={key}><strong>Baris {key.split('.')[1]}:</strong> {message}</li>)}
                                </ul>
                            </div>
                        )}

                        <div className="flex justify-end">
                            <button type="submit" disabled={!data.file || processing} className="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">{processing ? 'Memvalidasi...' : 'Validasi & Import'}</button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
