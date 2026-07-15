import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { FormEvent, useState } from 'react';

type Investigation = {
    id: number; investigation_number: string; title: string; status: string;
    root_cause: string | null; recommendations: string | null;
    five_whys: Array<{ level: number; question: string; answer: string; is_root_cause: boolean }> | null;
    fishbone: Array<{ category: string; causes: string[] }> | null;
    started_at: string | null; completed_at: string | null; created_at: string;
    incident?: { incident_number: string; title: string } | null;
    investigator?: { name: string } | null;
    team_members?: { id: number; name: string; pivot: { role: string | null } }[];
};

type Comment = { id: number; body: string; created_at: string; author?: { name: string } | null };
type Activity = { id: number; event: string; description: string | null; created_at: string; actor_name: string | null };
type WorkflowHistory = { id: number; from_status: string | null; to_status: string; action_label: string; reason: string | null; created_at: string };
type Transition = { action_key: string; action_label: string; requires_reason: boolean };

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};
const statusLabels: Record<string, string> = { draft: 'Draft', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' };

export default function Show({ investigation, comments, activities, workflowHistory, availableTransitions, auth }: PageProps<{
    investigation: Investigation;
    comments: Comment[];
    activities: Activity[];
    workflowHistory: WorkflowHistory[];
    availableTransitions: Transition[];
}>) {
    const permissions = new Set(auth.permissions ?? []);
    const [showModal, setShowModal] = useState(false);
    const [modalAction, setModalAction] = useState<'complete' | 'cancel'>('complete');
    const [commentBody, setCommentBody] = useState('');
    const { data: modalData, setData: setModalData, post: postModal, processing } = useForm({ reason: '' });

    function doAction(action: string, requiresReason: boolean) {
        if (requiresReason) {
            setModalAction(action === 'complete' ? 'complete' : 'cancel');
            setShowModal(true);
            return;
        }
        router.post(route(`investigation.reports.${action}`, investigation.id));
    }

    function submitModal(e: FormEvent) {
        e.preventDefault();
        postModal(route(`investigation.reports.${modalAction}`, investigation.id), { onSuccess: () => setShowModal(false) });
    }

    function submitComment(e: FormEvent) {
        e.preventDefault();
        if (!commentBody.trim()) return;
        router.post('/core/comments', { module_name: 'investigation', reference_id: investigation.id, body: commentBody }, { onSuccess: () => setCommentBody('') });
    }

    const canEdit = (investigation.status === 'draft' || investigation.status === 'in_progress') && permissions.has('investigation.reports.update');
    const actionColors: Record<string, string> = { start: 'bg-indigo-600 hover:bg-indigo-700', complete: 'bg-green-600 hover:bg-green-700', cancel: 'bg-red-600 hover:bg-red-700' };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{investigation.investigation_number}</h2>}>
            <Head title={`Investigasi ${investigation.investigation_number}`} />
            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Summary */}
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div className="flex flex-wrap items-center gap-3">
                            <span className="text-2xl font-bold text-gray-900 dark:text-gray-100">{investigation.investigation_number}</span>
                            <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusColors[investigation.status] ?? ''}`}>{statusLabels[investigation.status] ?? investigation.status}</span>
                        </div>
                        <h1 className="mt-3 text-xl font-semibold text-gray-900 dark:text-gray-100">{investigation.title}</h1>
                        <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span>Insiden: {investigation.incident ? <Link href={route('incident.reports.show', investigation.incident)} className="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{investigation.incident.incident_number}</Link> : '-'}</span>
                            <span>Investigator: {investigation.investigator?.name ?? '-'}</span>
                            {investigation.started_at && <span>Mulai: {new Date(investigation.started_at).toLocaleString('id-ID')}</span>}
                            {investigation.completed_at && <span>Selesai: {new Date(investigation.completed_at).toLocaleString('id-ID')}</span>}
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex flex-wrap gap-2">
                        <Link href={route('investigation.reports.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Kembali</Link>
<>                        {canEdit && <Link href={route('investigation.reports.edit', investigation.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Edit</Link>}
                        <DeleteWithConfirm
                            routeName="investigation.reports.destroy"
                            id={investigation.id}
                            permission="investigation.reports.delete"
                            itemLabel={investigation.investigation_number}
                            redirectTo="investigation.reports.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
                        {availableTransitions.map((t) => {
                            const permKey = t.action_key === 'start' ? 'submit' : t.action_key === 'complete' ? 'close' : 'update';
                            if (!permissions.has(`investigation.reports.${permKey}`)) return null;
                            return (
                                <button key={t.action_key} onClick={() => doAction(t.action_key, t.requires_reason)} className={`rounded-md px-4 py-2 text-sm font-medium text-white ${actionColors[t.action_key] ?? 'bg-gray-600 hover:bg-gray-700'}`}>
                                    {t.action_label}
                                </button>
                            );
                        })}
                    </div>

                    {/* Root Cause */}
                    {investigation.root_cause && (
                        <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Root Cause</h3>
                            <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{investigation.root_cause}</p>
                        </div>
                    )}

                    {/* 5-Why */}
                    {investigation.five_whys && investigation.five_whys.length > 0 && (
                        <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">5-Why Analysis</h3>
                            <div className="space-y-3">
                                {investigation.five_whys.map((w, i) => (
                                    <div key={i} className="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                        <p className="text-sm font-medium text-gray-700 dark:text-gray-300">Why {w.level}: {w.question}</p>
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">→ {w.answer}</p>
                                        {w.is_root_cause && <span className="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-200">Root Cause</span>}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Fishbone */}
                    {investigation.fishbone && investigation.fishbone.length > 0 && (
                        <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Fishbone (Ishikawa)</h3>
                            <div className="grid gap-2 md:grid-cols-2">
                                {investigation.fishbone.map((f, i) => (
                                    <div key={i} className="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                        <p className="text-sm font-medium text-gray-700 dark:text-gray-300">{f.category}</p>
                                        {f.causes.length > 0 ? <ul className="mt-1 text-sm text-gray-600 dark:text-gray-400">{f.causes.map((c, j) => <li key={j}>• {c}</li>)}</ul> : <p className="text-xs text-gray-400">No causes</p>}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Recommendations */}
                    {investigation.recommendations && (
                        <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Rekomendasi</h3>
                            <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{investigation.recommendations}</p>
                        </div>
                    )}

                    {/* Team */}
                    {investigation.team_members && investigation.team_members.length > 0 && (
                        <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Tim Investigasi</h3>
                            <ul className="space-y-2">
                                {investigation.team_members.map((m) => (
                                    <li key={m.id} className="text-sm text-gray-700 dark:text-gray-300">• {m.name}{m.pivot?.role && <span className="text-gray-500"> ({m.pivot.role})</span>}</li>
                                ))}
                            </ul>
                        </div>
                    )}

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

            {/* Modal for complete/cancel with reason */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{modalAction === 'complete' ? 'Selesaikan Investigasi' : 'Batalkan Investigasi'}</h3>
                        <form onSubmit={submitModal}>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan *</label>
                            <textarea value={modalData.reason} onChange={(e) => setModalData('reason', e.target.value)} rows={3} autoFocus className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <div className="mt-4 flex justify-end gap-2">
                                <button type="button" onClick={() => setShowModal(false)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</button>
                                <button type="submit" disabled={processing} className={`rounded-md px-4 py-2 text-sm font-medium text-white ${modalAction === 'complete' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}`}>{modalAction === 'complete' ? 'Selesaikan' : 'Batalkan'}</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
