import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, Ncr, NcrStatus, Site, Department, Severity } from '@/types';
import { useState } from 'react';

interface FormProps extends PageProps {
    ncr?: Ncr | null;
    sites: Site[];
    departments: Department[];
    severities: Severity[];
    sources: Record<string, string>;
    statuses?: Record<string, string>;
}

interface NcrFormData {
    title: string;
    source: string;
    site_id: string;
    department_id: string;
    severity_id: string;
    product_service: string;
    batch_lot: string;
    customer_name: string;
    description: string;
    root_cause: string;
    corrective_action: string;
    preventive_action: string;
    status: NcrStatus;
}

export default function Form({ auth, ncr, sites, departments, severities, sources, statuses }: FormProps) {
    const isEdit = !!ncr;

    const { data, setData, post, put, processing, errors } = useForm<NcrFormData>({
        title: ncr?.title ?? '',
        source: ncr?.source ?? '',
        site_id: ncr?.site_id ? String(ncr.site_id) : '',
        department_id: ncr?.department_id ? String(ncr.department_id) : '',
        severity_id: ncr?.severity_id ? String(ncr.severity_id) : '',
        product_service: ncr?.product_service ?? '',
        batch_lot: ncr?.batch_lot ?? '',
        customer_name: ncr?.customer_name ?? '',
        description: ncr?.description ?? '',
        root_cause: ncr?.root_cause ?? '',
        corrective_action: ncr?.corrective_action ?? '',
        preventive_action: ncr?.preventive_action ?? '',
        status: ncr?.status ?? 'open',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit && ncr) {
            put(route('quality.ncrs.update', ncr.id));
        } else {
            post(route('quality.ncrs.store'));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('quality.ncrs.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit NCR' : 'Buat NCR'}</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{isEdit ? 'Perbarui data NCR' : 'Isi data laporan ketidaksesuaian dengan lengkap'}</p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit NCR' : 'Buat NCR'} />
            <div className="py-6">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {isEdit && (
                            <div className="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                Nomor: <span className="font-mono">{ncr?.ncr_number}</span>
                            </div>
                        )}

                        <Section title="Informasi Umum">
                            <Field label="Judul *" error={errors.title}>
                                <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Masukkan judul ketidaksesuaian..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>

                            <Field label="Sumber *" error={errors.source}>
                                <select value={data.source} onChange={(e) => setData('source', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Sumber —</option>
                                    {Object.entries(sources).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </Field>

                            <Field label="Site *" error={errors.site_id}>
                                <select value={data.site_id} onChange={(e) => setData('site_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Site —</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>

                            <Field label="Departemen" error={errors.department_id}>
                                <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Departemen —</option>
                                    {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </Field>

                            <Field label="Severity *" error={errors.severity_id}>
                                <select value={data.severity_id} onChange={(e) => setData('severity_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Severity —</option>
                                    {severities.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>
                        </Section>

                        <Section title="Detail Produk/Jasa">
                            <Field label="Produk/Jasa" error={errors.product_service}>
                                <input type="text" value={data.product_service} onChange={(e) => setData('product_service', e.target.value)} placeholder="Nama produk atau jasa..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Batch/Lot" error={errors.batch_lot}>
                                <input type="text" value={data.batch_lot} onChange={(e) => setData('batch_lot', e.target.value)} placeholder="Nomor batch/lot..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Nama Pelanggan" error={errors.customer_name}>
                                <input type="text" value={data.customer_name} onChange={(e) => setData('customer_name', e.target.value)} placeholder="Nama pelanggan (jika relevan)..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                        </Section>

                        <Section title="Deskripsi Ketidaksesuaian">
                            <Field label="Deskripsi *" error={errors.description}>
                                <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={5} placeholder="Jelaskan ketidaksesuaian secara detail..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                        </Section>

                        <Section title="Analisis & Tindakan">
                            <Field label="Akar Masalah (Root Cause)" error={errors.root_cause}>
                                <textarea value={data.root_cause} onChange={(e) => setData('root_cause', e.target.value)} rows={4} placeholder="Jelaskan akar masalah..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Tindakan Korektif" error={errors.corrective_action}>
                                <textarea value={data.corrective_action} onChange={(e) => setData('corrective_action', e.target.value)} rows={4} placeholder="Jelaskan tindakan korektif..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Tindakan Preventif" error={errors.preventive_action}>
                                <textarea value={data.preventive_action} onChange={(e) => setData('preventive_action', e.target.value)} rows={4} placeholder="Jelaskan tindakan preventif..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                        </Section>

                        {isEdit && statuses && (
                            <Section title="Status">
                                <Field label="Status" error={errors.status}>
                                    <select value={data.status} onChange={(e) => setData('status', e.target.value as NcrStatus)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        {Object.entries(statuses).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </Field>
                            </Section>
                        )}

                        <div className="sticky bottom-0 flex items-center justify-between border-t border-gray-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-gray-700 dark:bg-gray-800/90">
                            <Link href={route('quality.ncrs.index')} className="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300">← Batal</Link>
                            <button type="submit" disabled={processing} className="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                                {processing ? 'Menyimpan...' : 'Simpan'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{title}</h3>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">{children}</div>
        </div>
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
