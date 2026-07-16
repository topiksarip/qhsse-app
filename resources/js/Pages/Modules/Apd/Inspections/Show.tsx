import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { PageProps } from '@/types';

interface ManagedFile {
    id: number;
    original_name: string;
    mime_type: string;
    url?: string;
}

interface Activity {
    id: number;
    event: string;
    description: string | null;
    actor_name: string | null;
    created_at: string;
}

interface Inspection {
    id: number;
    item: {
        id: number;
        item_number: string;
        status: string;
        condition: string;
        catalog?: { name: string };
        site?: { name: string };
    } | null;
    inspection_type: string;
    inspection_date: string | null;
    result: string;
    condition: string | null;
    next_inspection_date: string | null;
    notes: string | null;
    inspector?: { name: string } | null;
}

interface CapaAction {
    id: number;
    action_number: string;
    title: string;
    status: string;
    source_type?: string | null;
}

type Props = {
    inspection: Inspection;
    files: ManagedFile[];
    activities: Activity[];
    capaActions?: CapaAction[];
    can?: { escalate?: boolean };
    users?: { id: number; name: string }[];
    priorities?: { id: number; name: string }[];
};

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString('id-ID') : '-');
const formatDateTime = (value: string) => new Date(value).toLocaleString('id-ID');

export default function Show({ inspection, files, activities, capaActions = [], can, users = [], priorities = [] }: PageProps<Props>) {
    const resultBadge =
        inspection.result === 'tidak_layak'
            ? 'inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300'
            : 'inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/inspections" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Inspeksi
                    </Link>
                    <h2 className="text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                        Inspeksi {inspection.item?.item_number ?? ''}
                    </h2>
                </div>
            }
        >
            <Head title="Detail Inspeksi APD" />
            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field label="Item" value={inspection.item ? `${inspection.item.item_number} — ${inspection.item.catalog?.name ?? '?'}` : '-'} />
                            <Field label="Site" value={inspection.item?.site?.name ?? '-'} />
                            <Field label="Tipe Inspeksi" value={inspection.inspection_type} />
                            <Field label="Tgl Inspeksi" value={formatDate(inspection.inspection_date)} />
                            <Field label="Hasil" value={<span className={resultBadge}>{inspection.result === 'tidak_layak' ? 'Tidak Layak' : 'Layak'}</span>} />
                            <Field label="Kondisi Item" value={inspection.condition ?? '-'} />
                            <Field label="Inspektor" value={inspection.inspector?.name ?? '-'} />
                            <Field label="Jadwal Berikutnya" value={formatDate(inspection.next_inspection_date)} />
                        </div>
                        {inspection.notes && (
                            <div className="mt-4">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan</p>
                                <p className="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{inspection.notes}</p>
                            </div>
                        )}
                    </div>

                    {files.length > 0 && (
                        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <h3 className="mb-3 text-lg font-bold text-slate-900 dark:text-white">Foto Bukti</h3>
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                {files.map((f) => (
                                    <a
                                        key={f.id}
                                        href={f.url ?? '#'}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="flex flex-col items-center rounded-md border border-slate-200 p-2 text-center text-xs text-blue-600 hover:underline dark:border-gray-700 dark:text-blue-400"
                                    >
                                        {f.mime_type?.startsWith('image/') ? (
                                            <img src={f.url} alt={f.original_name} className="h-20 w-full rounded object-cover" />
                                        ) : (
                                            <span className="flex h-20 w-full items-center justify-center rounded bg-slate-100 text-gray-500 dark:bg-gray-800">📄</span>
                                        )}
                                        <span className="mt-1 line-clamp-2">{f.original_name}</span>
                                    </a>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 className="mb-3 text-lg font-bold text-slate-900 dark:text-white">Aktivitas</h3>
                        {activities.length === 0 ? (
                            <p className="text-sm text-gray-500">Belum ada aktivitas.</p>
                        ) : (
                            <ul className="space-y-2">
                                {activities.map((a) => (
                                    <li key={a.id} className="flex items-start justify-between gap-3 text-sm">
                                        <span>
                                            <span className="font-medium text-slate-800 dark:text-slate-100">{a.description ?? a.event}</span>
                                            <span className="ml-2 text-xs text-gray-400">{a.actor_name ?? '-'}</span>
                                        </span>
                                        <span className="shrink-0 text-xs text-gray-400">{formatDateTime(a.created_at)}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <CapaEscalationSection
                        inspection={inspection}
                        capaActions={capaActions}
                        users={users ?? []}
                        priorities={priorities ?? []}
                        canEscalate={Boolean(can?.escalate)}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
            <div className="mt-1 text-sm text-slate-800 dark:text-slate-100">{value}</div>
        </div>
    );
}

function CapaEscalationSection({
    inspection,
    capaActions,
    users,
    priorities,
    canEscalate,
}: {
    inspection: Inspection;
    capaActions: CapaAction[];
    users: { id: number; name: string }[];
    priorities: { id: number; name: string }[];
    canEscalate: boolean;
}) {
    const [assignedTo, setAssignedTo] = useState<string>('');
    const [priorityId, setPriorityId] = useState<string>('');
    const [error, setError] = useState<string | null>(null);
    const isUnfit = inspection.result === 'tidak_layak';

    function escalate(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        if (!assignedTo || !priorityId) {
            setError('Pilih penanggung jawab dan prioritas.');
            return;
        }
        router.post(
            route('apd.inspections.escalate', inspection.id),
            { assigned_to: Number(assignedTo), priority_id: Number(priorityId) },
            { preserveScroll: true, onError: (err: Record<string, string>) => setError(Object.values(err).join(' ')) },
        );
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 className="mb-3 text-lg font-bold text-slate-900 dark:text-white">CAPA Terkait</h3>

            {capaActions.length === 0 ? (
                <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada CAPA yang di-eskalasi dari inspeksi ini.</p>
            ) : (
                <ul className="mb-4 space-y-2">
                    {capaActions.map((c) => (
                        <li key={c.id} className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-gray-700">
                            <Link href={route('capa.actions.show', c.id)} className="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                {c.action_number} — {c.title}
                            </Link>
                            <span className="text-xs text-gray-400">{c.status}</span>
                        </li>
                    ))}
                </ul>
            )}

            {canEscalate && isUnfit && (
                <form onSubmit={escalate} className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <select value={assignedTo} onChange={(e) => setAssignedTo(e.target.value)} className="rounded-md border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                        <option value="">Penanggung Jawab…</option>
                        {users.map((u) => (
                            <option key={u.id} value={u.id}>{u.name}</option>
                        ))}
                    </select>
                    <select value={priorityId} onChange={(e) => setPriorityId(e.target.value)} className="rounded-md border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                        <option value="">Prioritas…</option>
                        {priorities.map((p) => (
                            <option key={p.id} value={p.id}>{p.name}</option>
                        ))}
                    </select>
                    <button type="submit" className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Eskalasi ke CAPA
                    </button>
                    {error && <p className="sm:col-span-3 text-xs text-red-600">{error}</p>}
                </form>
            )}
            {canEscalate && !isUnfit && (
                <p className="text-xs text-gray-400">Hanya inspeksi <span className="font-medium">tidak layak</span> yang dapat dieskalasi ke CAPA.</p>
            )}
        </div>
    );
}
