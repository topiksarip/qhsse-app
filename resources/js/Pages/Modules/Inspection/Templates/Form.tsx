import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Item = { id?: number; question: string; type: string; category: string | null; is_required: boolean; order: number };
type Template = { id: number; code: string; name: string; description: string | null; category: string; items: Item[] } | null;

const itemTypes = [{ value: 'yes_no', label: 'Yes/No' }, { value: 'yes_no_na', label: 'Yes/No/N/A' }, { value: 'safe_unsafe', label: 'Safe/Unsafe' }, { value: 'na', label: 'N/A or OK' }, { value: 'scale', label: 'Scale 1-5' }, { value: 'text', label: 'Text' }, { value: 'photo', label: 'Upload Foto' }];

export default function TemplateForm({ item }: PageProps<{ item: Template }>) {
    const isEdit = item !== null;
    const [items, setItems] = useState<Item[]>(item?.items ?? []);
    const { data, setData, post, put, processing, errors } = useForm({
        code: item?.code ?? '', name: item?.name ?? '', description: item?.description ?? '', category: item?.category ?? 'safety',
    });

    function submit(e: FormEvent) { e.preventDefault(); const payload = { ...data, items }; if (isEdit) { put(route('inspection.templates.update', item!.id)); } else { post(route('inspection.templates.store')); } }
    function addItem() { setItems([...items, { question: '', type: 'yes_no', category: null, is_required: true, order: items.length }]); }
    function updateItem(index: number, field: keyof Item, value: string | boolean | number) { setItems(items.map((it, i) => i === index ? { ...it, [field]: value } : it)); }
    function removeItem(index: number) { setItems(items.filter((_, i) => i !== index)); }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Template' : 'Buat Template'}</h2>}>
            <Head title={isEdit ? 'Edit Template' : 'Buat Template'} />
            <div className="py-12"><div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Template</h3>
                    <form onSubmit={submit} className="grid gap-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Code *</label><input type="text" value={data.code} onChange={(e) => setData('code', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />{errors.code && <p className="mt-1 text-sm text-red-600">{errors.code}</p>}</div>
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Category *</label><select value={data.category} onChange={(e) => setData('category', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{['safety','environment','equipment','fire','housekeeping','security','quality','compliance'].map((c) => <option key={c} value={c}>{c}</option>)}</select></div>
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label><input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />{errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}</div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label><textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" /></div>

                        <div>
                            <div className="flex items-center justify-between"><h4 className="text-sm font-semibold text-gray-600 dark:text-gray-400">Items</h4><button type="button" onClick={addItem} className="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">+ Tambah Item</button></div>
                            <div className="mt-2 space-y-3">
                                {items.map((it, i) => (
                                    <div key={i} className="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                        <div className="grid gap-2 md:grid-cols-3">
                                            <input type="text" value={it.question} onChange={(e) => updateItem(i, 'question', e.target.value)} placeholder="Question..." className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 md:col-span-2" />
                                            <select value={it.type} onChange={(e) => updateItem(i, 'type', e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{itemTypes.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}</select>
                                        </div>
                                        <div className="mt-2 flex items-center gap-3">
                                            <label className="text-sm text-gray-600 dark:text-gray-400"><input type="checkbox" checked={it.is_required} onChange={(e) => updateItem(i, 'is_required', e.target.checked)} className="mr-1" />Required</label>
                                            <button type="button" onClick={() => removeItem(i)} className="ml-auto text-sm text-red-600 hover:text-red-800">Remove</button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="flex justify-between">
                            <Link href={route('inspection.templates.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</Link>
                            <button type="submit" disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{isEdit ? 'Update' : 'Simpan'}</button>
                        </div>
                    </form>
                </div>
            </div></div>
        </AuthenticatedLayout>
    );
}
