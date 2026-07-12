import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, RiskRegister, RiskRegisterType, Site, Department, Area, Severity, RiskMatrixLevel, User } from '@/types';
import { useState } from 'react';
import RiskMatrixGrid from '@/Components/Risk/RiskMatrixGrid';

interface FormProps extends PageProps {
    riskRegister?: RiskRegister | null;
    sites: Site[];
    areas: Area[];
    departments: Department[];
    severities: Severity[];
    riskMatrixLevels: RiskMatrixLevel[];
    users: User[];
}

interface RiskFormData {
    title: string;
    type: RiskRegisterType;
    site_id: string;
    area_id: string;
    department_id: string;
    activity: string;
    hazard: string;
    existing_controls: string;
    severity_id: string;
    probability_id: string;
    risk_level_id: string;
    additional_controls: string;
    residual_severity_id: string;
    residual_probability_id: string;
    residual_risk_level_id: string;
    owner_id: string;
    review_date: string;
}

const probabilityLabels: Record<number, string> = {
    1: 'Jarang',
    2: 'Tidak Mungkin',
    3: 'Mungkin',
    4: 'Kemungkinan Besar',
    5: 'Hampir Pasti',
};

export default function Form({ auth, riskRegister, sites, areas, departments, severities, riskMatrixLevels, users }: FormProps) {
    const isEdit = !!riskRegister;

    const { data, setData, post, put, processing, errors } = useForm<RiskFormData>({
        title: riskRegister?.title ?? '',
        type: riskRegister?.type ?? 'hiradc',
        site_id: riskRegister?.site_id ? String(riskRegister.site_id) : '',
        area_id: riskRegister?.area_id ? String(riskRegister.area_id) : '',
        department_id: riskRegister?.department_id ? String(riskRegister.department_id) : '',
        activity: riskRegister?.activity ?? '',
        hazard: riskRegister?.hazard ?? '',
        existing_controls: riskRegister?.existing_controls ?? '',
        severity_id: riskRegister?.severity_id ? String(riskRegister.severity_id) : '',
        probability_id: riskRegister?.probability_id ? String(riskRegister.probability_id) : '',
        risk_level_id: riskRegister?.risk_level_id ? String(riskRegister.risk_level_id) : '',
        additional_controls: riskRegister?.additional_controls ?? '',
        residual_severity_id: riskRegister?.residual_severity_id ? String(riskRegister.residual_severity_id) : '',
        residual_probability_id: riskRegister?.residual_probability_id ? String(riskRegister.residual_probability_id) : '',
        residual_risk_level_id: riskRegister?.residual_risk_level_id ? String(riskRegister.residual_risk_level_id) : '',
        owner_id: riskRegister?.owner_id ? String(riskRegister.owner_id) : '',
        review_date: riskRegister?.review_date ?? '',
    });

    // Areas/departments filtered by selected site
    const siteAreas = areas.filter((a) => !data.site_id || a.site_id === Number(data.site_id));
    const siteDepartments = departments.filter((d) => !d.site_id || d.site_id === Number(data.site_id));

    function findLevel(severityId: string, probabilityId: string): RiskMatrixLevel | undefined {
        if (!severityId || !probabilityId) return undefined;
        const sev = severities.find((s) => s.id === Number(severityId));
        if (!sev) return undefined;
        return riskMatrixLevels.find((m) => m.consequence === sev.level && m.likelihood === Number(probabilityId));
    }

    function onInitialSelect(severityId: number, probabilityId: number, riskLevelId: number) {
        setData('severity_id', String(severityId));
        setData('probability_id', String(probabilityId));
        setData('risk_level_id', String(riskLevelId));
    }

    function onResidualSelect(severityId: number, probabilityId: number, riskLevelId: number) {
        setData('residual_severity_id', String(severityId));
        setData('residual_probability_id', String(probabilityId));
        setData('residual_risk_level_id', String(riskLevelId));
    }

    const initialLevel = findLevel(data.severity_id, data.probability_id);
    const residualLevel = findLevel(data.residual_severity_id, data.residual_probability_id);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isEdit && riskRegister) {
            put(route('risk.registers.update', riskRegister.id));
        } else {
            post(route('risk.registers.store'));
        }
    }

    const typeOptions: RiskRegisterType[] = ['hazard_identification', 'jsa', 'hiradc', 'risk_assessment'];

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('risk.registers.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Risk Register' : 'Buat Risk Register'}</h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Risk Register' : 'Buat Risk Register'} />
            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {isEdit && (
                            <div className="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                Nomor: <span className="font-mono">{riskRegister?.register_number}</span>
                            </div>
                        )}

                        <Section title="Informasi Umum">
                            <Field label="Judul *" error={errors.title}>
                                <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} placeholder="Judul risiko..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Tipe *" error={errors.type}>
                                <div className="flex flex-wrap gap-3 pt-2">
                                    {typeOptions.map((t) => (
                                        <label key={t} className="inline-flex items-center gap-2 text-sm">
                                            <input type="radio" name="type" checked={data.type === t} onChange={() => setData('type', t)} className="text-indigo-600" />
                                            {t === 'hazard_identification' ? 'Hazard ID' : t === 'jsa' ? 'JSA' : t === 'hiradc' ? 'HIRADC' : 'Risk Assessment'}
                                        </label>
                                    ))}
                                </div>
                            </Field>
                            <Field label="Site *" error={errors.site_id}>
                                <select value={data.site_id} onChange={(e) => { setData('site_id', e.target.value); setData('area_id', ''); setData('department_id', ''); }} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Pilih Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>
                            <Field label="Area" error={errors.area_id}>
                                <select value={data.area_id} onChange={(e) => setData('area_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Pilih Area</option>
                                    {siteAreas.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                </select>
                            </Field>
                            <Field label="Department" error={errors.department_id}>
                                <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Pilih Department</option>
                                    {siteDepartments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </Field>
                            <Field label="Aktivitas *" error={errors.activity}>
                                <input type="text" value={data.activity} onChange={(e) => setData('activity', e.target.value)} placeholder="Aktivitas kerja..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Hazard *" error={errors.hazard}>
                                <textarea value={data.hazard} onChange={(e) => setData('hazard', e.target.value)} rows={3} placeholder="Bahaya (hazard)..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Existing Controls" error={errors.existing_controls}>
                                <textarea value={data.existing_controls} onChange={(e) => setData('existing_controls', e.target.value)} rows={2} placeholder="Kontrol yang sudah ada..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                            <Field label="Owner *" error={errors.owner_id}>
                                <select value={data.owner_id} onChange={(e) => setData('owner_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Pilih Owner</option>
                                    {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                                </select>
                            </Field>
                            <Field label="Review Date" error={errors.review_date}>
                                <input type="date" value={data.review_date} onChange={(e) => setData('review_date', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                            </Field>
                        </Section>

                        <Section title="Initial Risk Assessment (Sebelum Kontrol)">
                            <Field label="Severity *" error={errors.severity_id}>
                                <select value={data.severity_id} onChange={(e) => setData('severity_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Pilih Severity</option>
                                    {severities.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                            </Field>
                            <Field label="Probability *" error={errors.probability_id}>
                                <select value={data.probability_id} onChange={(e) => setData('probability_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    <option value="">Pilih Probability</option>
                                    {[1, 2, 3, 4, 5].map((p) => <option key={p} value={p}>{p} — {probabilityLabels[p]}</option>)}
                                </select>
                            </Field>
                            <div className="md:col-span-2">
                                <RiskMatrixGrid
                                    severities={severities}
                                    matrixLevels={riskMatrixLevels}
                                    selectedSeverityId={data.severity_id ? Number(data.severity_id) : null}
                                    selectedProbabilityId={data.probability_id ? Number(data.probability_id) : null}
                                    onSelect={onInitialSelect}
                                    caption="Klik sel untuk memilih kombinasi severity × probability"
                                />
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Initial Risk Level: <span className="font-semibold">{initialLevel ? `${initialLevel.level.toUpperCase()} (${initialLevel.score})` : '—'}</span>
                                </p>
                            </div>
                        </Section>

                        {(data.additional_controls || isEdit) && (
                            <Section title="Residual Risk Assessment (Setelah Kontrol Tambahan)">
                                <Field label="Additional Controls" error={errors.additional_controls}>
                                    <textarea value={data.additional_controls} onChange={(e) => setData('additional_controls', e.target.value)} rows={3} placeholder="Tindakan kontrol tambahan..." className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                </Field>
                                <Field label="Residual Severity" error={errors.residual_severity_id}>
                                    <select value={data.residual_severity_id} onChange={(e) => setData('residual_severity_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        <option value="">Pilih Residual Severity</option>
                                        {severities.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                    </select>
                                </Field>
                                <Field label="Residual Probability" error={errors.residual_probability_id}>
                                    <select value={data.residual_probability_id} onChange={(e) => setData('residual_probability_id', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                        <option value="">Pilih Residual Probability</option>
                                        {[1, 2, 3, 4, 5].map((p) => <option key={p} value={p}>{p} — {probabilityLabels[p]}</option>)}
                                    </select>
                                </Field>
                                <div className="md:col-span-2">
                                    <RiskMatrixGrid
                                        severities={severities}
                                        matrixLevels={riskMatrixLevels}
                                        selectedSeverityId={data.residual_severity_id ? Number(data.residual_severity_id) : null}
                                        selectedProbabilityId={data.residual_probability_id ? Number(data.residual_probability_id) : null}
                                        onSelect={onResidualSelect}
                                        caption="Klik sel untuk memilih kombinasi residual severity × probability"
                                    />
                                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Residual Risk Level: <span className="font-semibold">{residualLevel ? `${residualLevel.level.toUpperCase()} (${residualLevel.score})` : '—'}</span>
                                    </p>
                                </div>
                            </Section>
                        )}

                        <div className="sticky bottom-0 flex items-center justify-between border-t border-gray-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-gray-700 dark:bg-gray-800/90">
                            <Link href={route('risk.registers.index')} className="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300">← Batal</Link>
                            <button type="submit" disabled={processing} className="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                                {processing ? 'Menyimpan...' : 'Simpan Risk Register'}
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
