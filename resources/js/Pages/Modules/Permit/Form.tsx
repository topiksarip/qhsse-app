import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, Permit, Site, Area, Department, Company, PermitType, SelectAsset, SelectEmployee } from '@/types';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect';
import { FormEventHandler, useState } from 'react';

interface FormProps extends PageProps {
    permit?: Permit | null;
    sites: Site[];
    areas: (Area & { site_id: number })[];
    departments: Department[];
    contractors: Company[];
    types: Record<string, string>;
    riskLevels: Record<string, string>;
    assets: SelectAsset[];
    employees: SelectEmployee[];
}

const checklistTemplates: Record<PermitType, string[]> = {
    hot_work: [
        'APD tahan api tersedia dan dipakai (goggles, gloves, apron)',
        'Fire extinguisher tersedia di area kerja (min. 2 unit)',
        'Area 10 meter bebas bahan mudah terbakar',
        'Fire watch ditunjuk dan siap',
        'Hot work permit area di-barricade',
        'Sistem ventilasi memadai',
        'Emergency response plan diketahui semua pekerja',
    ],
    working_at_height: [
        'Full body harness dipakai dan di-inspect',
        'Anchor point terverifikasi (min. 22 kN)',
        'Scaffolding di-inspect oleh competent person',
        'Edge protection / guard rail terpasang',
        'Fall protection system aktif',
        'Tidak ada pekerjaan di bawah area tanpa proteksi',
        'Emergency rescue plan siap',
    ],
    confined_space: [
        'Gas test dilakukan (O2, LEL, H2S, CO)',
        'Ventilasi mekanis aktif',
        'Entry permit ditandatangani',
        'Standby person ditunjuk di entrance',
        'Rescue equipment siap (tripod, winch, SCBA)',
        'Komunikasi antara entrant dan attendant',
        'Lockout/Tagout semua sumber energi',
        'Continuous gas monitoring aktif',
    ],
    electrical: [
        'LOTO procedure dijalankan dan diverifikasi',
        'Voltage test dilakukan (verify zero energy)',
        'PPE electrical rated dipakai (gloves, mats)',
        'Grounding temporary terpasang',
        'Barricade dan warning sign terpasang',
        'Competent person melakukan pekerjaan',
        'Emergency procedure untuk electrical shock diketahui',
    ],
    excavation: [
        'Underground utility scan dilakukan dan didokumentasikan',
        'Shoring/sloping sesuai depth (≥ 1.2m wajib shoring)',
        'Safe access/egress (ladder setiap 7.5m)',
        'Spoil pile ≥ 0.6m dari edge',
        'Gas test untuk confined space trench',
        'Barricade dan warning sign terpasang',
        'Daily inspection oleh competent person',
    ],
    lifting: [
        'Lift plan disiapkan dan di-approve',
        'Load calculation dilakukan',
        'Crane/hoist certification valid',
        'Rigger dan signalman certified',
        'Sling dan rigging gear di-inspect',
        'Area lifting di-barricade',
        'Weather condition sesuai (wind speed < limit)',
        'Communication radio tersedia',
    ],
    other: [
        'Risk assessment / JSA dilakukan',
        'APD sesuai pekerjaan dipakai',
        'Emergency procedure diketahui',
        'Pekerja competent dan tersertifikasi',
        'Area kerja di-barricade',
    ],
};

const inputClass = 'w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';

export default function Form({ auth, permit, sites, areas, departments, contractors, types, riskLevels, assets, employees }: FormProps) {
    const isEdit = !!permit;
    const [type, setType] = useState<PermitType | ''>(permit?.type || '');
    const [checklist, setChecklist] = useState<string[]>(permit ? [] : []);
    const [startDt, setStartDt] = useState(permit?.start_datetime ? toLocalInput(permit.start_datetime) : '');
    const [endDt, setEndDt] = useState(permit?.end_datetime ? toLocalInput(permit.end_datetime) : '');
    const [workerIds, setWorkerIds] = useState<string[]>(
        (permit?.permit_workers ?? []).map((w) => String(w.employee_id)),
    );
    const [workerRoles, setWorkerRoles] = useState<Record<string, string>>(
        Object.fromEntries((permit?.permit_workers ?? []).map((w) => [String(w.employee_id), w.role ?? ''])),
    );
    const [assetIds, setAssetIds] = useState<string[]>(
        (permit?.permit_assets ?? []).map((a) => String(a.asset_id)),
    );
    const [assetRoles, setAssetRoles] = useState<Record<string, string>>(
        Object.fromEntries((permit?.permit_assets ?? []).map((a) => [String(a.asset_id), a.role ?? ''])),
    );

    const { data, setData, post, put, processing, errors } = useForm({
        type: permit?.type || '',
        title: permit?.title || '',
        description: permit?.description || '',
        site_id: permit?.site_id || '',
        area_id: permit?.area_id || '',
        department_id: permit?.department_id || '',
        contractor_id: permit?.contractor_id || '',
        work_location: permit?.work_location || '',
        work_description: permit?.work_description || '',
        start_datetime: permit?.start_datetime || '',
        end_datetime: permit?.end_datetime || '',
        risk_level: permit?.risk_level || '',
        jsa_reference: permit?.jsa_reference || '',
        worker_ids: workerIds,
        worker_roles: workerRoles,
        asset_ids: assetIds,
        asset_roles: assetRoles,
    });

    function toLocalInput(dt: string): string {
        const d = new Date(dt);
        const off = d.getTimezoneOffset();
        return new Date(d.getTime() - off * 60000).toISOString().slice(0, 16);
    }

    function handleType(t: PermitType | '') {
        setType(t);
        setData('type', t);
        if (t) setChecklist(checklistTemplates[t] || []);
        else setChecklist([]);
    }

    function handleWorkers(next: string[]) {
        setWorkerIds(next);
        setData('worker_ids', next);
        // drop roles for removed workers
        const cleaned: Record<string, string> = {};
        next.forEach((id) => { cleaned[id] = workerRoles[id] ?? ''; });
        setWorkerRoles(cleaned);
        setData('worker_roles', cleaned);
    }

    function handleWorkerRole(id: string, role: string) {
        const next = { ...workerRoles, [id]: role };
        setWorkerRoles(next);
        setData('worker_roles', next);
    }

    function handleAssets(next: string[]) {
        setAssetIds(next);
        setData('asset_ids', next);
        const cleaned: Record<string, string> = {};
        next.forEach((id) => { cleaned[id] = assetRoles[id] ?? ''; });
        setAssetRoles(cleaned);
        setData('asset_roles', cleaned);
    }

    function handleAssetRole(id: string, role: string) {
        const next = { ...assetRoles, [id]: role };
        setAssetRoles(next);
        setData('asset_roles', next);
    }

    function computedHours(): number | null {
        if (!startDt || !endDt) return null;
        const s = new Date(startDt);
        const e = new Date(endDt);
        if (isNaN(s.getTime()) || isNaN(e.getTime()) || e <= s) return null;
        return Math.floor((e.getTime() - s.getTime()) / 3600000);
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setData('start_datetime', startDt ? new Date(startDt).toISOString() : '');
        setData('end_datetime', endDt ? new Date(endDt).toISOString() : '');
        if (isEdit && permit) {
            put(route('permit.work.update', permit.id));
        } else {
            post(route('permit.work.store'));
        }
    }

    const areaOptions = areas.filter((a) => !data.site_id || a.site_id === Number(data.site_id));
    const hours = computedHours();
    const typeEntries = Object.entries(types);
    const riskEntries = Object.entries(riskLevels);

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Izin Kerja' : 'Buat Izin Kerja'}</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{isEdit ? 'Perbarui data izin kerja' : 'Isi data izin kerja dengan lengkap'}</p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Izin Kerja' : 'Buat Izin Kerja'} />
            <div className="py-6">
                <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Informasi Izin */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">INFORMASI IZIN</h3>
                            <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div>
                                    <span className={labelClass}>Nomor Izin</span>
                                    <input disabled value={isEdit ? permit!.permit_number : 'Auto-generated (PTW-XXXX)'} className={`${inputClass} font-mono opacity-70`} />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Nomor akan dibuat otomatis saat simpan.</p>
                                </div>
                                <div>
                                    <span className={labelClass}>Jenis Izin <span className="text-red-500">*</span></span>
                                    <select value={data.type} onChange={(e) => handleType(e.target.value as PermitType | '')} className={`${inputClass} ${errors.type ? 'border-red-500' : ''}`} required>
                                        <option value="">— Pilih Jenis Izin —</option>
                                        {typeEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                    {errors.type && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.type}</p>}
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">⚠ Pemilihan jenis menentukan checklist dinamis.</p>
                                </div>
                                <div>
                                    <span className={labelClass}>Judul <span className="text-red-500">*</span></span>
                                    <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} maxLength={255} className={`${inputClass} ${errors.title ? 'border-red-500' : ''}`} placeholder="Masukkan judul izin kerja..." required />
                                    {errors.title && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.title}</p>}
                                </div>
                                <div>
                                    <span className={labelClass}>Deskripsi <span className="text-red-500">*</span></span>
                                    <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} className={`${inputClass} ${errors.description ? 'border-red-500' : ''}`} placeholder="Jelaskan ringkasan izin kerja..." required />
                                    {errors.description && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Lokasi & Pekerja */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">LOKASI & PEKERJA</h3>
                            <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span className={labelClass}>Site <span className="text-red-500">*</span></span>
                                        <select value={data.site_id} onChange={(e) => { setData('site_id', e.target.value); setData('area_id', ''); }} className={`${inputClass} ${errors.site_id ? 'border-red-500' : ''}`} required>
                                            <option value="">— Pilih Site —</option>
                                            {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                        </select>
                                        {errors.site_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.site_id}</p>}
                                    </div>
                                    <div>
                                        <span className={labelClass}>Area</span>
                                        <select value={data.area_id} onChange={(e) => setData('area_id', e.target.value)} className={inputClass} disabled={areaOptions.length === 0}>
                                            <option value="">— Pilih Area —</option>
                                            {areaOptions.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span className={labelClass}>Department</span>
                                        <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className={inputClass}>
                                            <option value="">— Pilih Department —</option>
                                            {departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <span className={labelClass}>Contractor</span>
                                        <select value={data.contractor_id} onChange={(e) => setData('contractor_id', e.target.value)} className={inputClass}>
                                            <option value="">— Pilih Contractor —</option>
                                            {contractors.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <span className={labelClass}>Lokasi Kerja <span className="text-red-500">*</span></span>
                                    <input type="text" value={data.work_location} onChange={(e) => setData('work_location', e.target.value)} className={`${inputClass} ${errors.work_location ? 'border-red-500' : ''}`} placeholder="Lokasi spesifik pekerjaan..." required />
                                    {errors.work_location && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.work_location}</p>}
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Contoh: "Tower B Lantai 3, Area Welding Bay"</p>
                                </div>
                                <div>
                                    <span className={labelClass}>Deskripsi Pekerjaan <span className="text-red-500">*</span></span>
                                    <textarea value={data.work_description} onChange={(e) => setData('work_description', e.target.value)} rows={3} className={`${inputClass} ${errors.work_description ? 'border-red-500' : ''}`} placeholder="Jelaskan detail pekerjaan yang akan dilakukan..." required />
                                    {errors.work_description && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.work_description}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Alat & Pekerja */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">ALAT & PEKERJA</h3>
                            <div className="space-y-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div>
                                    <span className={labelClass}>Alat / Peralatan (opsional)</span>
                                    <SearchableMultiSelect
                                        options={assets.map((a) => ({ value: String(a.id), label: `${a.asset_number} — ${a.name}` }))}
                                        value={assetIds}
                                        onChange={handleAssets}
                                        placeholder="Cari & pilih alat..."
                                    />
                                    {assetIds.length > 0 && (
                                        <div className="mt-3 space-y-2">
                                            {assetIds.map((id) => (
                                                <div key={id} className="flex items-center gap-2">
                                                    <span className="w-64 truncate text-sm text-gray-700 dark:text-gray-300">
                                                        {assets.find((a) => String(a.id) === id)?.asset_number} — {assets.find((a) => String(a.id) === id)?.name}
                                                    </span>
                                                    <input
                                                        type="text"
                                                        value={assetRoles[id] ?? ''}
                                                        onChange={(e) => handleAssetRole(id, e.target.value)}
                                                        className={`${inputClass} flex-1`}
                                                        placeholder="Peran alat (opsional)"
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <span className={labelClass}>Pekerja <span className="text-red-500">*</span></span>
                                    <SearchableMultiSelect
                                        options={employees.map((e) => ({ value: String(e.id), label: e.employee_no ? `${e.employee_no} — ${e.name}` : e.name }))}
                                        value={workerIds}
                                        onChange={handleWorkers}
                                        placeholder="Cari & pilih pekerja..."
                                    />
                                    {errors.worker_ids && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.worker_ids}</p>}
                                    {workerIds.length > 0 && (
                                        <div className="mt-3 space-y-2">
                                            {workerIds.map((id) => (
                                                <div key={id} className="flex items-center gap-2">
                                                    <span className="w-64 truncate text-sm text-gray-700 dark:text-gray-300">
                                                        {employees.find((e) => String(e.id) === id)?.employee_no} — {employees.find((e) => String(e.id) === id)?.name}
                                                    </span>
                                                    <input
                                                        type="text"
                                                        value={workerRoles[id] ?? ''}
                                                        onChange={(e) => handleWorkerRole(id, e.target.value)}
                                                        className={`${inputClass} flex-1`}
                                                        placeholder="Peran (mis. operator, pengawas)"
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimal 1 pekerja wajib dipilih.</p>
                                </div>
                            </div>
                        </div>

                        {/* Periode & Risiko */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">PERIODE & RISIKO</h3>
                            <div className="space-y-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span className={labelClass}>Mulai Berlaku <span className="text-red-500">*</span></span>
                                        <input type="datetime-local" value={startDt} onChange={(e) => setStartDt(e.target.value)} className={`${inputClass} ${errors.start_datetime ? 'border-red-500' : ''}`} required />
                                        {errors.start_datetime && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.start_datetime}</p>}
                                    </div>
                                    <div>
                                        <span className={labelClass}>Berakhir Pada <span className="text-red-500">*</span></span>
                                        <input type="datetime-local" value={endDt} onChange={(e) => setEndDt(e.target.value)} className={`${inputClass} ${errors.end_datetime ? 'border-red-500' : ''}`} required />
                                        {errors.end_datetime && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.end_datetime}</p>}
                                    </div>
                                </div>
                                <div>
                                    <span className={labelClass}>Durasi</span>
                                    <input disabled value={hours !== null ? `${hours} jam` : 'Auto-calculated'} className={`${inputClass} opacity-70`} />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Dihitung otomatis dari selisih mulai dan berakhir.</p>
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span className={labelClass}>Risk Level</span>
                                        <select value={data.risk_level} onChange={(e) => setData('risk_level', e.target.value)} className={inputClass}>
                                            <option value="">— Pilih Risk Level —</option>
                                            {riskEntries.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <span className={labelClass}>JSA Reference</span>
                                        <input type="text" value={data.jsa_reference} onChange={(e) => setData('jsa_reference', e.target.value)} className={inputClass} placeholder="Nomor referensi JSA/Risk Assessment..." />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Checklist Dinamis */}
                        {type && (
                            <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <h3 className="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">CHECKLIST KESELAMATAN</h3>
                                <p className="mb-3 text-xs text-gray-500 dark:text-gray-400">⚠ Checklist otomatis dibuat berdasarkan jenis izin. Checklist harus di-sign setelah izin di-approve.</p>
                                <div className="space-y-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                                    {checklist.length === 0 ? (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Pilih jenis izin untuk melihat checklist.</p>
                                    ) : checklist.map((item, i) => (
                                        <div key={i} className="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                            ☐ {item}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Action Bar */}
                        <div className="sticky bottom-0 flex items-center justify-between gap-2 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                            <Link href={route('permit.work.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">← Batal</Link>
                            <div className="flex gap-2">
                                <button type="submit" disabled={processing} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                                    {isEdit ? 'Simpan' : 'Simpan Draft'}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}