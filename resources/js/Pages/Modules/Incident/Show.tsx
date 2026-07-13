import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Incident = {
    id: number;
    incident_number: string;
    title: string;
    category: string;
    occurred_at: string;
    status: string;
    description: string;
    immediate_action: string | null;
    site?: { name: string } | null;
    area?: { name: string } | null;
    department?: { name: string } | null;
    reporter?: { name: string } | null;
    severity?: { name: string; color: string; level: number } | null;
    priority?: { name: string; color: string; level: number } | null;
    involved_persons?: { id: number; name: string; pivot: { note: string | null } }[];
};

type ManagedFile = { id: number; original_name: string; size: number; mime_type: string };
type Comment = { id: number; body: string; created_at: string; author?: { name: string } | null };
type Activity = { id: number; event: string; description: string | null; created_at: string; actor_name: string | null };
type WorkflowHistory = { id: number; from_status: string | null; to_status: string; action_label: string; reason: string | null; created_at: string };
type Transition = { action_key: string; action_label: string; requires_reason: boolean };
type ReasonAction = 'reject' | 'close';

const categoryLabels: Record<string, string> = {
    accident: 'Accident', incident: 'Incident', near_miss: 'Near Miss', unsafe_act: 'Unsafe Act',
    unsafe_condition: 'Unsafe Condition', environmental_spill: 'Env. Spill', security_breach: 'Security Breach',
};
const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    under_review: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};
const statusLabels: Record<string, string> = { draft: 'Draft', submitted: 'Submitted', under_review: 'Under Review', closed: 'Closed', rejected: 'Rejected' };

export default function Show({ incident, evidence, comments, activities, workflowHistory, availableTransitions, auth }: PageProps<{
    incident: Incident;
    evidence: ManagedFile[];
    comments: Comment[];
    activities: Activity[];
    workflowHistory: WorkflowHistory[];
    availableTransitions: Transition[];
}>) {
    const permissions = new Set(auth.permissions ?? []);
    const [reasonAction, setReasonAction] = useState<ReasonAction | null>(null);
    const [commentBody, setCommentBody] = useState('');
    const { data: reasonData, setData: setReasonData, post: postReason, processing: transitioning, errors: reasonErrors, reset: resetReason } = useForm({ reason: '' });

    function doAction(action: string, requiresReason = false) {
        if (requiresReason && (action === 'close' || action === 'reject')) {
            setReasonAction(action);
            return;
        }
        router.post(route(`incident.reports.${action}`, incident.id));
    }

    function submitReason(e: FormEvent) {
        e.preventDefault();
        if (!reasonAction) return;
        postReason(route(`incident.reports.${reasonAction}`, incident.id), {
            onSuccess: () => { setReasonAction(null); resetReason(); },
        });
    }

    function submitEvidence(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        const form = e.currentTarget;
        router.post(route('incident.reports.evidence.store', incident.id), new FormData(form), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    }

    function submitComment(e: FormEvent) {
        e.preventDefault();
        if (!commentBody.trim()) return;
        router.post('/core/comments', { module_name: 'incident', reference_id: incident.id, body: commentBody }, { onSuccess: () => setCommentBody('') });
    }

    const canEdit = incident.status === 'draft' && permissions.has('incident.reports.update');

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{incident.incident_number}</h2>}>
            <Head title={`Insiden ${incident.incident_number}`} />
            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Summary */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="text-2xl font-bold text-gray-900 dark:text-gray-100">{incident.incident_number}</span>
                            <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusColors[incident.status] ?? ''}`}>{statusLabels[incident.status] ?? incident.status}</span>
                            {incident.severity && <span className="inline-flex rounded-full px-3 py-1 text-sm font-semibold" style={{ backgroundColor: `${incident.severity.color}20`, color: incident.severity.color }}>{incident.severity.name}</span>}
                            {incident.priority && <span className="inline-flex rounded-full px-3 py-1 text-sm font-semibold" style={{ backgroundColor: `${incident.priority.color}20`, color: incident.priority.color }}>{incident.priority.name}</span>}
                        </div>
                        <h1 className="mt-3 text-xl font-semibold text-gray-900 dark:text-gray-100">{incident.title}</h1>
                        <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span>Tanggal: {new Date(incident.occurred_at).toLocaleString('id-ID')}</span>
                            <span>Reporter: {incident.reporter?.name ?? '-'}</span>
                            <span>Site: {incident.site?.name ?? '-'}</span>
                            {incident.area && <span>Area: {incident.area.name}</span>}
                            {incident.department && <span>Dept: {incident.department.name}</span>}
                            <span>Kategori: {categoryLabels[incident.category] ?? incident.category}</span>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex flex-wrap gap-2">
                        <Link href={route('incident.reports.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Kembali</Link>
                        {canEdit && <Link href={route('incident.reports.edit', incident.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Edit</Link>}
                        {permissions.has('incident.reports.export') && <a href={route('incident.reports.print', incident.id)} target="_blank" rel="noreferrer" className="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Cetak / PDF</a>}
                        {availableTransitions.map((t) => {
                            const actionPermissions: Record<string, string> = { submit: 'submit', review: 'review', reject: 'review', close: 'close' };
                            const canDo = permissions.has(`incident.reports.${actionPermissions[t.action_key]}`);
                            if (!canDo) return null;
                            const colors: Record<string, string> = { submit: 'bg-indigo-600 hover:bg-indigo-700', review: 'bg-blue-600 hover:bg-blue-700', reject: 'bg-red-600 hover:bg-red-700', close: 'bg-green-600 hover:bg-green-700' };
                            return (
                                <button key={t.action_key} onClick={() => doAction(t.action_key, t.requires_reason)} className={`rounded-md px-4 py-2 text-sm font-medium text-white ${colors[t.action_key] ?? 'bg-gray-600 hover:bg-gray-700'}`}>
                                    {t.action_label}
                                </button>
                            );
                        })}
                    </div>

                    {/* Deskripsi */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Deskripsi</h3>
                        <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{incident.description}</p>
                        {incident.immediate_action && (
                            <div className="mt-4">
                                <h4 className="text-sm font-semibold text-gray-600 dark:text-gray-400">Tindakan Immediate</h4>
                                <p className="mt-1 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{incident.immediate_action}</p>
                            </div>
                        )}
                    </div>

                    {/* Involved Persons */}
                    {incident.involved_persons && incident.involved_persons.length > 0 && (
                        <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Orang Terlibat</h3>
                            <ul className="space-y-2">
                                {incident.involved_persons.map((p) => (
                                    <li key={p.id} className="text-sm text-gray-700 dark:text-gray-300">• {p.name}{p.pivot?.note && <span className="text-gray-500"> ({p.pivot.note})</span>}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Evidence */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Evidence</h3>
                        {permissions.has('incident.reports.evidence') && !['closed', 'rejected'].includes(incident.status) && (
                            <form onSubmit={submitEvidence} className="mb-4 flex flex-col gap-2 rounded-lg border border-dashed border-gray-300 p-4 sm:flex-row sm:items-center dark:border-gray-600">
                                <input type="file" name="file" required accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt" className="min-w-0 flex-1 text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:text-gray-300" />
                                <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Unggah Evidence</button>
                            </form>
                        )}
                        {evidence.length === 0 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada evidence file.</p>
                        ) : (
                            <ul className="space-y-2">
                                {evidence.map((f) => (
                                    <li key={f.id} className="flex items-center justify-between text-sm">
                                        <span className="text-gray-700 dark:text-gray-300">📎 {f.original_name} ({(f.size / 1024).toFixed(0)}KB)</span>
                                        <a href={route('incident.reports.evidence.download', [incident.id, f.id])} className="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Download</a>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {/* Workflow Timeline */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Status Timeline</h3>
                        <div className="space-y-3">
                            {workflowHistory.map((h) => (
                                <div key={h.id} className="flex items-start gap-3 text-sm">
                                    <div className="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-600" />
                                    <div>
                                        <span className="font-medium text-gray-900 dark:text-gray-100">{h.action_label}</span>
                                        <span className="text-gray-500"> — {new Date(h.created_at).toLocaleString('id-ID')}</span>
                                        {h.reason && <p className="text-gray-500">{h.reason}</p>}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Comments */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Comments</h3>
                        <form onSubmit={submitComment} className="mb-4 flex gap-2">
                            <input type="text" value={commentBody} onChange={(e) => setCommentBody(e.target.value)} placeholder="Tulis komentar..." className="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Kirim</button>
                        </form>
                        <div className="space-y-3">
                            {comments.map((c) => (
                                <div key={c.id} className="border-b border-gray-100 pb-3 dark:border-gray-700">
                                    <div className="flex items-center gap-2 text-sm">
                                        <span className="font-medium text-gray-900 dark:text-gray-100">{c.author?.name ?? 'Unknown'}</span>
                                        <span className="text-gray-400">{new Date(c.created_at).toLocaleString('id-ID')}</span>
                                    </div>
                                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">{c.body}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Activity Log */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Activity Log</h3>
                        <div className="space-y-2">
                            {activities.map((a) => (
                                <div key={a.id} className="text-sm text-gray-500 dark:text-gray-400">
                                    <span className="text-gray-400">{new Date(a.created_at).toLocaleString('id-ID')}</span> — {a.description ?? a.event} {a.actor_name && <span className="text-gray-400">({a.actor_name})</span>}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {/* Reason Modal */}
            {reasonAction && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{reasonAction === 'reject' ? 'Tolak' : 'Tutup'} Laporan Insiden</h3>
                        <form onSubmit={submitReason}>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan *</label>
                            <textarea value={reasonData.reason} onChange={(e) => setReasonData('reason', e.target.value)} rows={3} autoFocus className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            {reasonErrors.reason && <p className="mt-1 text-sm text-red-600">{reasonErrors.reason}</p>}
                            <div className="mt-4 flex justify-end gap-2">
                                <button type="button" onClick={() => setReasonAction(null)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</button>
                                <button type="submit" disabled={transitioning} className={`rounded-md px-4 py-2 text-sm font-medium text-white ${reasonAction === 'reject' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'}`}>{reasonAction === 'reject' ? 'Tolak' : 'Tutup'}</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
