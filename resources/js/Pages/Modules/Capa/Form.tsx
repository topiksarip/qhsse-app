import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type MasterData = { id: number; name: string; [key: string]: unknown };

type CapaItem = {
    id: number; action_number: string; title: string; description: string; status: string;
    source_module: string | null; source_reference_id: number | null; source_type: string | null;
    site_id: number; department_id: number | null; assigned_to: number; due_date: string | null;
    severity_id: number | null; priority_id: number;
};

type CapaPrefill = Partial<Pick<CapaItem,
    'title' | 'description' | 'source_module' | 'source_reference_id' |
    'source_type' | 'site_id' | 'department_id'
>>;

type Props = {
    item: CapaItem | null;
    prefill?: CapaPrefill;
    sites: MasterData[]; departments: (MasterData & { site_id: number })[];
    severities: (MasterData & { level: number; color: string })[];
    priorities: (MasterData & { sla_days: number; color: string })[];
    users: MasterData[];
};

export default function Form({ item, prefill = {}, sites, departments, severities, priorities, users }: PageProps<Props>) {
    const isEdit = item !== null;
    const isEditable = !item || ['open', 'in_progress', 'rejected'].includes(item.status);

    const { data, setData, post, put, processing, errors } = useForm({
        title: item?.title ?? prefill.title ?? '',
        description: item?.description ?? prefill.description ?? '',
        source_module: item?.source_module ?? prefill.source_module ?? 'manual',
        source_reference_id: item?.source_reference_id?.toString() ?? prefill.source_reference_id?.toString() ?? '',
        source_type: item?.source_type ?? prefill.source_type ?? 'corrective',
        site_id: item?.site_id ?? prefill.site_id ?? '',
        department_id: item?.department_id ?? prefill.department_id ?? '',
        assigned_to: item?.assigned_to ?? '',
        due_date: item?.due_date ?? '',
        severity_id: item?.severity_id ?? '',
        priority_id: item?.priority_id ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (isEdit) { put(route('capa.actions.update', item.id)); } else { post(route('capa.actions.store')); }
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit CAPA' : 'Buat CAPA'}</h2>}>
            <Head title={isEdit ? 'Edit CAPA' : 'Buat CAPA'} />
            <div className="py-12"><div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                {isEdit && (
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor CAPA</label>
                        <input type="text" value={item.action_number} disabled className="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400" />
                    </div>
                )}
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Action</h3>
                    <div className="grid gap-4">
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Judul *</label>
                            <input type="text" value={data.title} onChange={(e) => setData('title', e.target.value)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi *</label>
                            <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} disabled={!isEditable} rows={4} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Source</label>
                                <select value={data.source_module} onChange={(e) => setData('source_module', e.target.value)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="manual">Manual</option><option value="incident">Incident</option><option value="inspection">Inspection</option><option value="asset_inspection">Inspeksi Aset</option><option value="audit">Audit</option>
                                </select>
                            </div>
                            <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                <select value={data.source_type} onChange={(e) => setData('source_type', e.target.value)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="corrective">Corrective</option><option value="preventive">Preventive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Assignment & Lokasi</h3>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Site *</label>
                            <select value={data.site_id} onChange={(e) => setData('site_id', Number(e.target.value))} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Pilih Site</option>{sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>{errors.site_id && <p className="mt-1 text-sm text-red-600">{errors.site_id}</p>}
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                            <select value={data.department_id} onChange={(e) => setData('department_id', e.target.value as unknown as number)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Pilih Department</option>{departments.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                            </select>
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">PIC (Assigned To) *</label>
                            <select value={data.assigned_to} onChange={(e) => setData('assigned_to', Number(e.target.value))} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Pilih PIC</option>{users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>{errors.assigned_to && <p className="mt-1 text-sm text-red-600">{errors.assigned_to}</p>}
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                            <input type="date" value={data.due_date} onChange={(e) => setData('due_date', e.target.value)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Severity</label>
                            <select value={data.severity_id} onChange={(e) => setData('severity_id', e.target.value as unknown as number)} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Pilih Severity</option>{severities.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                        <div><label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority *</label>
                            <select value={data.priority_id} onChange={(e) => setData('priority_id', Number(e.target.value))} disabled={!isEditable} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                <option value="">Pilih Priority</option>{priorities.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>{errors.priority_id && <p className="mt-1 text-sm text-red-600">{errors.priority_id}</p>}
                        </div>
                    </div>
                </div>
                <div className="flex justify-between">
                    <Link href={route('capa.actions.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</Link>
                    {isEditable && <button type="button" onClick={submit} disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{isEdit ? 'Update' : 'Simpan'}</button>}
                </div>
            </div></div>
        </AuthenticatedLayout>
    );
}
