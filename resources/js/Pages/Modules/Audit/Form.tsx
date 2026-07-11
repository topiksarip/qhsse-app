import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Option = { id: number; name: string };
type AuditItem = {
    id: number;
    audit_number: string;
    title: string;
    type: string;
    scope: string | null;
    summary: string | null;
    scheduled_date: string | null;
    department_id: number | null;
    lead_auditor_id: number | null;
    status: string;
};

const auditTypes = [
    { value: 'internal', label: 'Internal' },
    { value: 'external', label: 'Eksternal' },
    { value: 'supplier', label: 'Pemasok' },
    { value: 'regulator', label: 'Regulator' },
];

export default function Form({ item, departments, users }: PageProps<{
    item: AuditItem | null; departments: Option[]; users: Option[];
}>) {
    const editing = item !== null;
    const { data, setData, post, processing, errors } = useForm({
        title: item?.title ?? '',
        audit_type: item?.type ?? 'internal',
        scope: item?.scope ?? '',
        summary: item?.summary ?? '',
        department_id: item?.department_id ? String(item.department_id) : '',
        lead_auditor_id: item?.lead_auditor_id ? String(item.lead_auditor_id) : '',
        scheduled_date: item?.scheduled_date ?? '',
        _method: editing ? 'put' : 'post',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post(editing ? route('audit.management.update', item.id) : route('audit.management.store'), { forceFormData: true });
    }

    const inputClass = 'mt-1 block w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white';
    const Error = ({ name }: { name: keyof typeof errors }) => errors[name] ? <p className="mt-1 text-sm text-red-600">{errors[name]}</p> : null;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">{editing ? 'Edit Audit' : 'Buat Audit'}</h2>}>
            <Head title={editing ? 'Edit Audit' : 'Buat Audit'} />
            <div className="py-10"><form onSubmit={submit} className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div className="mb-5 flex items-start justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Identitas Audit</h3>
                            <p className="text-sm text-slate-500">Nomor audit dibuat otomatis dan tidak dapat dipakai ulang.</p>
                        </div>
                        {editing && <span className="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">{item.audit_number}</span>}
                    </div>
                    <div className="grid gap-5 md:grid-cols-2">
                        <div className="md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Judul *</label>
                            <input value={data.title} onChange={(e) => setData('title', e.target.value)} className={inputClass} />
                            <Error name="title" />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Jenis Audit *</label>
                            <select value={data.audit_type} onChange={(e) => setData('audit_type', e.target.value)} className={inputClass}>
                                {auditTypes.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                            </select>
                            <Error name="audit_type" />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal Jadwal</label>
                            <input type="date" value={data.scheduled_date} onChange={(e) => setData('scheduled_date', e.target.value)} className={inputClass} />
                            <Error name="scheduled_date" />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Department</label>
                            <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className={inputClass}>
                                <option value="">Lintas department</option>
                                {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                            <Error name="department_id" />
                        </div>
                        <div>
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Auditor Utama</label>
                            <select value={data.lead_auditor_id} onChange={(e) => setData('lead_auditor_id', e.target.value)} className={inputClass}>
                                <option value="">Pilih auditor</option>
                                {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                            <Error name="lead_auditor_id" />
                        </div>
                        <div className="md:col-span-2">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Ruang Lingkup</label>
                            <textarea rows={4} value={data.scope} onChange={(e) => setData('scope', e.target.value)} className={inputClass} placeholder="Deskripsikan ruang lingkup audit..." />
                            <Error name="scope" />
                        </div>
                        {editing && (
                            <div className="md:col-span-2">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Ringkasan {item.status === 'in_progress' && <span className="text-red-500">* (wajib saat Buat Laporan)</span>}</label>
                                <textarea rows={4} value={data.summary} onChange={(e) => setData('summary', e.target.value)} className={inputClass} placeholder="Ringkasan hasil audit..." />
                                <Error name="summary" />
                            </div>
                        )}
                    </div>
                </section>

                <div className="flex flex-wrap justify-between gap-3">
                    <Link href={editing ? route('audit.management.show', item.id) : route('audit.management.index')} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</Link>
                    <button type="submit" disabled={processing} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{editing ? 'Simpan Perubahan' : 'Buat Audit'}</button>
                </div>
            </form></div>
        </AuthenticatedLayout>
    );
}
