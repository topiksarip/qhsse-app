import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Option = { id: number; name: string };
type TypeOption = { value: string; label: string };
type DocumentItem = {
    id: number; document_number: string; title: string; type: string; version: string; revision_notes: string | null;
    effective_date: string | null; review_date: string | null; expiry_date: string | null; department_id: number | null;
    owner_id: number; is_confidential: boolean; status: string;
};

export default function Form({ item, departments, users, documentTypes }: PageProps<{
    item: DocumentItem | null; departments: Option[]; users: Option[]; documentTypes: TypeOption[];
}>) {
    const editing = item !== null;
    const { data, setData, post, processing, errors, transform } = useForm({
        title: item?.title ?? '', type: item?.type ?? 'sop', version: item?.version ?? '1.0', revision_notes: item?.revision_notes ?? '',
        effective_date: item?.effective_date ?? '', review_date: item?.review_date ?? '', expiry_date: item?.expiry_date ?? '',
        department_id: item?.department_id ? String(item.department_id) : '', owner_id: item?.owner_id ? String(item.owner_id) : '',
        is_confidential: item?.is_confidential ?? false, action: 'draft', file: null as File | null, _method: editing ? 'put' : 'post',
    });

    function submit(event: FormEvent, action: 'draft' | 'submit_review' = 'draft') {
        event.preventDefault();
        transform((payload) => ({
            ...payload,
            action,
            department_id: payload.department_id || null,
            owner_id: payload.owner_id || null,
        }));
        post(editing ? route('document.control.update', item.id) : route('document.control.store'), { forceFormData: true });
    }

    const inputClass = 'mt-1 block w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
    const Error = ({ name }: { name: keyof typeof errors }) => errors[name] ? <p className="mt-1 text-sm text-red-600">{errors[name]}</p> : null;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">{editing ? 'Edit Dokumen' : 'Buat Dokumen'}</h2>}>
            <Head title={editing ? 'Edit Dokumen' : 'Buat Dokumen'} />
            <div className="py-10"><form className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div className="mb-5 flex items-start justify-between"><div><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Identitas Dokumen</h3><p className="text-sm text-slate-500">Nomor dokumen dibuat otomatis dan tidak dapat dipakai ulang.</p></div>{editing && <span className="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{item.document_number}</span>}</div>
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="md:col-span-2"><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Judul *</label><input value={data.title} onChange={(e) => setData('title', e.target.value)} className={inputClass} /><Error name="title" /></div>
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tipe *</label><select value={data.type} onChange={(e) => setData('type', e.target.value)} className={inputClass}>{documentTypes.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}</select><Error name="type" /></div>
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Versi *</label><input value={data.version} onChange={(e) => setData('version', e.target.value)} className={inputClass} /><Error name="version" /></div>
                        <div className="md:col-span-2"><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Catatan revisi</label><textarea rows={4} value={data.revision_notes} onChange={(e) => setData('revision_notes', e.target.value)} className={inputClass} /><Error name="revision_notes" /></div>
                    </div>
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="mb-5 text-lg font-semibold text-slate-900 dark:text-white">Kepemilikan & Masa Berlaku</h3>
                    <div className="grid gap-5 md:grid-cols-2">
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Department</label><select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className={inputClass}><option value="">Lintas department</option>{departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select><Error name="department_id" /></div>
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Owner</label><select value={data.owner_id} onChange={(e) => setData('owner_id', e.target.value)} className={inputClass}><option value="">User yang login</option>{users.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select><Error name="owner_id" /></div>
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal efektif</label><input type="date" value={data.effective_date} onChange={(e) => setData('effective_date', e.target.value)} className={inputClass} /><Error name="effective_date" /></div>
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal review</label><input type="date" value={data.review_date} onChange={(e) => setData('review_date', e.target.value)} className={inputClass} /><Error name="review_date" /></div>
                        <div><label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal kedaluwarsa</label><input type="date" value={data.expiry_date} onChange={(e) => setData('expiry_date', e.target.value)} className={inputClass} /><Error name="expiry_date" /></div>
                        <label className="flex items-center gap-3 self-end rounded-lg border border-purple-200 bg-purple-50 p-3 text-sm font-medium text-purple-800"><input type="checkbox" checked={data.is_confidential} onChange={(e) => setData('is_confidential', e.target.checked)} className="rounded border-purple-300 text-purple-600" />🔒 Dokumen rahasia</label>
                    </div>
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">File Terkontrol</h3><p className="mt-1 text-sm text-slate-500">PDF, DOC/DOCX, atau XLS/XLSX. Maksimum 10 MB. Disimpan pada private storage.</p><input type="file" accept=".pdf,.doc,.docx,.xls,.xlsx" onChange={(e) => setData('file', e.target.files?.[0] ?? null)} className="mt-4 block w-full rounded-lg border border-dashed border-slate-300 p-4 text-sm dark:border-gray-600 dark:text-slate-300" /><Error name="file" /></section>

                <div className="flex flex-wrap justify-between gap-3"><Link href={editing ? route('document.control.show', item.id) : route('document.control.index')} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</Link><div className="flex gap-2"><button type="button" onClick={(e) => submit(e, 'draft')} disabled={processing} className="rounded-lg border border-indigo-200 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">{editing ? 'Simpan Perubahan' : 'Simpan Draft'}</button>{!editing && <button type="button" onClick={(e) => submit(e, 'submit_review')} disabled={processing} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan & Submit Review</button>}</div></div>
            </form></div>
        </AuthenticatedLayout>
    );
}
