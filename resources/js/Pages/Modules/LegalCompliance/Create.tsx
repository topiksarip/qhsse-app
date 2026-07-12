import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, LegalRegister, LegalRegisterCategory, LegalComplianceStatus, LegalRegisterStatus, Site, Department, User } from '@/types';

interface FormProps extends PageProps {
    register?: LegalRegister | null;
    sites: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    users: { id: number; name: string }[];
    documents: { id: number; doc_number: string; title: string }[];
}

interface LegalFormData {
    title: string;
    regulation_name: string;
    regulation_number: string;
    issuing_body: string;
    category: LegalRegisterCategory;
    compliance_status: LegalComplianceStatus;
    site_id: string;
    department_id: string;
    owner_id: string;
    next_review_date: string;
    document_id: string;
    notes: string;
    status: LegalRegisterStatus;
}

const categories: { value: LegalRegisterCategory; label: string }[] = [
    { value: 'national', label: 'Nasional' },
    { value: 'regional', label: 'Regional' },
    { value: 'industry', label: 'Industri' },
    { value: 'internal', label: 'Internal' },
];

const complianceStatuses: { value: LegalComplianceStatus; label: string }[] = [
    { value: 'compliant', label: 'Patuh' },
    { value: 'non_compliant', label: 'Tidak Patuh' },
    { value: 'in_progress', label: 'Dalam Proses' },
    { value: 'not_applicable', label: 'Tidak Berlaku' },
];

const registerStatuses: { value: LegalRegisterStatus; label: string }[] = [
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Tidak Aktif' },
];

export default function LegalRegisterForm({ auth, register, sites, departments, users, documents }: FormProps) {
    const isEdit = !!register;

    const { data, setData, post, put, processing, errors } = useForm<LegalFormData>({
        title: register?.title ?? '',
        regulation_name: register?.regulation_name ?? '',
        regulation_number: register?.regulation_number ?? '',
        issuing_body: register?.issuing_body ?? '',
        category: register?.category ?? 'national',
        compliance_status: register?.compliance_status ?? 'in_progress',
        site_id: register?.site_id ? String(register.site_id) : '',
        department_id: register?.department_id ? String(register.department_id) : '',
        owner_id: register?.owner_id ? String(register.owner_id) : '',
        next_review_date: register?.next_review_date ?? '',
        document_id: register?.document_id ? String(register.document_id) : '',
        notes: register?.notes ?? '',
        status: register?.status ?? 'active',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit && register) {
            put(route('legal.registers.update', register.id));
        } else {
            post(route('legal.registers.store'));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('legal.registers.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Register' : 'Buat Register'}</h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Register' : 'Buat Register'} />
            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {isEdit && (
                        <div className="mb-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            Nomor: <span className="font-mono">{register?.register_number}</span>
                        </div>
                    )}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Informasi Regulasi</h3>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <Field label="Judul *" error={errors.title}>
                                    <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                                <Field label="Nama Regulasi *" error={errors.regulation_name}>
                                    <input type="text" value={data.regulation_name} onChange={(e) => setData('regulation_name', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                                <Field label="Nomor Regulasi *" error={errors.regulation_number}>
                                    <input type="text" value={data.regulation_number} onChange={(e) => setData('regulation_number', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                                <Field label="Instansi Penerbit *" error={errors.issuing_body}>
                                    <input type="text" value={data.issuing_body} onChange={(e) => setData('issuing_body', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                                <Field label="Kategori *" error={errors.category}>
                                    <select value={data.category} onChange={(e) => setData('category', e.target.value as LegalRegisterCategory)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                    </select>
                                </Field>
                                <Field label="Status Kepatuhan" error={errors.compliance_status}>
                                    <select value={data.compliance_status} onChange={(e) => setData('compliance_status', e.target.value as LegalComplianceStatus)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        {complianceStatuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                    </select>
                                </Field>
                                <Field label="Site" error={errors.site_id}>
                                    <select value={data.site_id} onChange={(e) => setData('site_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        <option value="">Pilih Site</option>
                                        {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                    </select>
                                </Field>
                                <Field label="Department" error={errors.department_id}>
                                    <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        <option value="">Pilih Department</option>
                                        {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                    </select>
                                </Field>
                                <Field label="Owner *" error={errors.owner_id}>
                                    <select value={data.owner_id} onChange={(e) => setData('owner_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        <option value="">Pilih Owner</option>
                                        {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                                    </select>
                                </Field>
                                <Field label="Tanggal Review Berikutnya" error={errors.next_review_date}>
                                    <input type="date" value={data.next_review_date} onChange={(e) => setData('next_review_date', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                                <Field label="Dokumen Terkait" error={errors.document_id}>
                                    <select value={data.document_id} onChange={(e) => setData('document_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        <option value="">Pilih Dokumen</option>
                                        {documents.map((d) => <option key={d.id} value={d.id}>{d.doc_number} — {d.title}</option>)}
                                    </select>
                                </Field>
                                <Field label="Record Status" error={errors.status}>
                                    <select value={data.status} onChange={(e) => setData('status', e.target.value as LegalRegisterStatus)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        {registerStatuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                                    </select>
                                </Field>
                                <Field label="Catatan" error={errors.notes}>
                                    <textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} rows={3} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                            </div>
                        </div>

                        <div className="sticky bottom-0 flex items-center justify-between border-t border-gray-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-gray-700 dark:bg-gray-800/90">
                            <Link href={route('legal.registers.index')} className="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300">← Batal</Link>
                            <button type="submit" disabled={processing} className="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                                {processing ? 'Menyimpan...' : 'Simpan Register'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="md:col-span-2">
            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}
