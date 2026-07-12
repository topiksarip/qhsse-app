import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, SecurityIncident, SecurityIncidentStatus, Site, Area, Severity } from '@/types';
import { useState } from 'react';

interface FormProps extends PageProps {
    incident?: SecurityIncident | null;
    sites: Site[];
    areas: (Area & { site_id: number })[];
    severities: Severity[];
    types: Record<string, string>;
    statuses?: Record<string, string>;
}

export default function Form({ auth, incident, sites, areas, severities, types, statuses }: FormProps) {
    const isEdit = !!incident;
    const [siteId, setSiteId] = useState<string>(incident?.site_id ? String(incident.site_id) : '');

    interface SecurityIncidentFormData {
        type: string;
        title: string;
        occurred_at: string;
        site_id: string;
        area_id: string;
        severity_id: string;
        description: string;
        status: SecurityIncidentStatus;
        resolution: string;
    }

    const { data, setData, post, put, processing, errors } = useForm<SecurityIncidentFormData>({
        type: incident?.type ?? '',
        title: incident?.title ?? '',
        occurred_at: incident?.occurred_at ? incident.occurred_at.slice(0, 16) : '',
        site_id: incident?.site_id ? String(incident.site_id) : '',
        area_id: incident?.area_id ? String(incident.area_id) : '',
        severity_id: incident?.severity_id ? String(incident.severity_id) : '',
        description: incident?.description ?? '',
        status: incident?.status ?? 'reported',
        resolution: incident?.resolution ?? '',
    });

    const filteredAreas = areas.filter((a) => String(a.site_id) === data.site_id);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit && incident) {
            put(route('security.incidents.update', incident.id));
        } else {
            post(route('security.incidents.store'));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('security.incidents.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Insiden Keamanan' : 'Laporkan Insiden Keamanan'}</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{isEdit ? 'Perbarui data insiden keamanan' : 'Isi data insiden keamanan dengan lengkap'}</p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Insiden Keamanan' : 'Laporkan Insiden Keamanan'} />
            <div className="py-6">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {isEdit && (
                            <div className="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                Nomor: <span className="font-mono">{incident?.security_number}</span>
                            </div>
                        )}

                        <Section title="Informasi Insiden">
                            <Field label="Tipe Insiden *" error={errors.type}>
                                <select value={data.type} onChange={(e) => setData('type', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Tipe —</option>
                                    {Object.entries(types).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </Field>

                            <Field label="Judul *" error={errors.title}>
                                <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Masukkan judul insiden..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>

                            <Field label="Waktu Kejadian *" error={errors.occurred_at}>
                                <input type="datetime-local" value={data.occurred_at} onChange={(e) => setData('occurred_at', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                        </Section>

                        <Section title="Lokasi">
                            <Field label="Site *" error={errors.site_id}>
                                <select value={data.site_id} onChange={(e) => { setData('site_id', e.target.value); setData('area_id', ''); setSiteId(e.target.value); }} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Site —</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>

                            <Field label="Area" error={errors.area_id}>
                                <select value={data.area_id} onChange={(e) => setData('area_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" disabled={!data.site_id}>
                                    <option value="">— Pilih Area —</option>
                                    {filteredAreas.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                </select>
                            </Field>
                        </Section>

                        <Section title="Klasifikasi">
                            <Field label="Severity *" error={errors.severity_id}>
                                <select value={data.severity_id} onChange={(e) => setData('severity_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">— Pilih Severity —</option>
                                    {severities.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>
                        </Section>

                        <Section title="Deskripsi">
                            <Field label="Deskripsi Insiden *" error={errors.description}>
                                <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={5} placeholder="Jelaskan kronologi insiden keamanan..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                        </Section>

                        {isEdit && statuses && (
                            <Section title="Status & Resolusi">
                                <Field label="Status" error={errors.status}>
                                    <select value={data.status} onChange={(e) => setData('status', e.target.value as SecurityIncidentStatus)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        {Object.entries(statuses).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </Field>
                                {data.status === 'closed' && (
                                    <Field label="Resolusi *" error={errors.resolution}>
                                        <textarea value={data.resolution} onChange={(e) => setData('resolution', e.target.value)} rows={4} placeholder="Jelaskan resolusi insiden..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                    </Field>
                                )}
                            </Section>
                        )}

                        <div className="sticky bottom-0 flex items-center justify-between border-t border-gray-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-gray-700 dark:bg-gray-800/90">
                            <Link href={route('security.incidents.index')} className="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300">← Batal</Link>
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
