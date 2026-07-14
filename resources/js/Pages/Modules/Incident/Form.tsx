import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type MasterData = {
    id: number;
    name: string;
    [key: string]: unknown;
};

type EmployeeOption = MasterData & { employee_no: string; site_id: number };
type InvolvedPerson = { employee_id: number | ''; note: string };

type Props = {
    item: {
        id: number;
        incident_number: string;
        title: string;
        category: string;
        occurred_at: string;
        site_id: number;
        area_id: number | null;
        department_id: number | null;
        severity_id: number;
        priority_id: number;
        description: string;
        immediate_action: string | null;
        status: string;
        involved_persons?: { id: number; pivot?: { note: string | null } }[];
    } | null;
    sites: MasterData[];
    areas: (MasterData & { site_id: number })[];
    departments: (MasterData & { site_id: number })[];
    severities: (MasterData & { level: number; color: string })[];
    priorities: (MasterData & { sla_days: number; color: string })[];
    employees: EmployeeOption[];
};

const categories = [
    { value: 'accident', label: 'Accident' },
    { value: 'incident', label: 'Incident' },
    { value: 'near_miss', label: 'Near Miss' },
    { value: 'unsafe_act', label: 'Unsafe Act' },
    { value: 'unsafe_condition', label: 'Unsafe Condition' },
    { value: 'environmental_spill', label: 'Environmental Spill' },
    { value: 'security_breach', label: 'Security Breach' },
];

export default function Form({ item, sites, areas, departments, severities, priorities, employees }: PageProps<Props>) {
    const isEdit = item !== null;
    const isDraft = !item || item.status === 'draft';

    const { data, setData, transform, post, put, processing, errors } = useForm({
        title: item?.title ?? '',
        category: item?.category ?? '',
        occurred_at: item?.occurred_at ?? '',
        site_id: item?.site_id ?? '',
        area_id: item?.area_id ?? '',
        department_id: item?.department_id ?? '',
        severity_id: item?.severity_id ?? '',
        priority_id: item?.priority_id ?? '',
        description: item?.description ?? '',
        immediate_action: item?.immediate_action ?? '',
        involved_persons: item?.involved_persons?.map((person) => ({
            employee_id: person.id,
            note: person.pivot?.note ?? '',
        })) ?? [] as InvolvedPerson[],
        action: 'draft' as 'draft' | 'submit',
    });

    function submit(e: FormEvent, action: 'draft' | 'submit') {
        e.preventDefault();
        transform((formData) => ({ ...formData, action }));
        if (isEdit) {
            put(route('incident.reports.update', item!.id));
        } else {
            post(route('incident.reports.store'));
        }
    }

    function changeSite(siteId: string) {
        setData((current) => ({
            ...current,
            site_id: siteId,
            area_id: '',
            department_id: '',
            involved_persons: [],
        }));
    }

    function updatePerson(index: number, field: keyof InvolvedPerson, value: string) {
        setData('involved_persons', data.involved_persons.map((person, personIndex) => personIndex === index
            ? { ...person, [field]: field === 'employee_id' ? Number(value) || '' : value }
            : person));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Laporan Insiden' : 'Buat Laporan Insiden'}</h2>}>
            <Head title={isEdit ? 'Edit Laporan Insiden' : 'Buat Laporan Insiden'} />
            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Informasi Umum */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Umum</h3>
                        <div className="grid gap-4">
                            {isEdit && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Insiden</label>
                                    <input type="text" value={item!.incident_number} disabled className="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400" />
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul *</label>
                                <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                                {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori *</label>
                                    <select value={data.category} onChange={(e) => setData('category', e.target.value)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                        <option value="">Pilih Kategori</option>
                                        {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                                    </select>
                                    {errors.category && <p className="mt-1 text-sm text-red-600">{errors.category}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Kejadian *</label>
                                    <input type="datetime-local" value={data.occurred_at ? data.occurred_at.slice(0, 16) : ''} onChange={(e) => setData('occurred_at', e.target.value)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                                    {errors.occurred_at && <p className="mt-1 text-sm text-red-600">{errors.occurred_at}</p>}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Lokasi */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Lokasi</h3>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Site *</label>
                                <select value={data.site_id} onChange={(e) => changeSite(e.target.value)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Site</option>
                                    {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                {errors.site_id && <p className="mt-1 text-sm text-red-600">{errors.site_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Area</label>
                                <select value={data.area_id} onChange={(e) => setData('area_id', e.target.value as unknown as number)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Area</option>
                                    {areas.filter((a) => !data.site_id || a.site_id === Number(data.site_id)).map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                                <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value as unknown as number)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Department</option>
                                    {departments.filter((d) => !data.site_id || d.site_id === Number(data.site_id)).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Klasifikasi */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Klasifikasi</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Severity *</label>
                                <select value={data.severity_id} onChange={(e) => setData('severity_id', e.target.value as unknown as number)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Severity</option>
                                    {severities.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                {errors.severity_id && <p className="mt-1 text-sm text-red-600">{errors.severity_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority *</label>
                                <select value={data.priority_id} onChange={(e) => setData('priority_id', e.target.value as unknown as number)} disabled={!isDraft} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="">Pilih Priority</option>
                                    {priorities.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                </select>
                                {errors.priority_id && <p className="mt-1 text-sm text-red-600">{errors.priority_id}</p>}
                            </div>
                        </div>
                    </div>

                    {/* Orang Terlibat */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Orang Terlibat</h3>
                                <p className="mt-1 text-sm text-gray-500">Tambahkan karyawan dari site kejadian bila relevan.</p>
                            </div>
                            {isDraft && (
                                <button type="button" onClick={() => setData('involved_persons', [...data.involved_persons, { employee_id: '', note: '' }])} disabled={!data.site_id} className="rounded-md bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 disabled:opacity-50">
                                    Tambah Orang
                                </button>
                            )}
                        </div>
                        <div className="mt-4 space-y-3">
                            {data.involved_persons.length === 0 && <p className="text-sm text-gray-500">Belum ada orang terlibat.</p>}
                            {data.involved_persons.map((person, index) => (
                                <div key={index} className="grid gap-3 rounded-lg border border-gray-200 p-3 md:grid-cols-[1fr_1fr_auto] dark:border-gray-700">
                                    <div>
                                        <select value={person.employee_id} onChange={(event) => updatePerson(index, 'employee_id', event.target.value)} disabled={!isDraft} className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                            <option value="">Pilih Karyawan</option>
                                            {employees.filter((employee) => employee.site_id === Number(data.site_id)).map((employee) => (
                                                <option key={employee.id} value={employee.id}>{employee.employee_no} — {employee.name}</option>
                                            ))}
                                        </select>
                                        {errors[`involved_persons.${index}.employee_id`] && <p className="mt-1 text-sm text-red-600">{errors[`involved_persons.${index}.employee_id`]}</p>}
                                    </div>
                                    <input type="text" value={person.note} onChange={(event) => updatePerson(index, 'note', event.target.value)} disabled={!isDraft} placeholder="Catatan keterlibatan" className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                                    {isDraft && <button type="button" onClick={() => setData('involved_persons', data.involved_persons.filter((_, personIndex) => personIndex !== index))} className="rounded-md px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Hapus</button>}
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Deskripsi */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Deskripsi</h3>
                        <div className="grid gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi *</label>
                                <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} disabled={!isDraft} rows={4} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Tindakan Immediate</label>
                                <textarea value={data.immediate_action} onChange={(e) => setData('immediate_action', e.target.value)} disabled={!isDraft} rows={3} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            </div>
                        </div>
                    </div>

                    {/* Buttons */}
                    <div className="flex justify-between">
                        <Link href={route('incident.reports.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Batal</Link>
                        {isDraft && (
                            <div className="flex gap-2">
                                <button type="button" onClick={(e) => submit(e, 'draft')} disabled={processing} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">Simpan Draft</button>
                                <button type="button" onClick={(e) => submit(e, 'submit')} disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Submit</button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
