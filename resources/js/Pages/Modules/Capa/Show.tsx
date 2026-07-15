import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { FormEvent, useState } from 'react';

type CapaAction = {
    id: number; action_number: string; title: string; description: string; status: string;
    source_module: string | null; source_type: string | null; due_date: string | null;
    assigned_at: string | null; verification_note: string | null;
    verified_at: string | null; closed_at: string | null; created_at: string;
    site?: { name: string } | null; department?: { name: string } | null;
    assignedTo?: { name: string } | null; assignedBy?: { name: string } | null;
    severity?: { name: string; color: string } | null; priority?: { name: string; color: string } | null;
    verifiedBy?: { name: string } | null;
};

type Comment = { id: number; body: string; created_at: string; author?: { name: string } | null };
type Activity = { id: number; event: string; description: string | null; created_at: string; actor_name: string | null };
type WorkflowHistory = { id: number; from_status: string | null; to_status: string; action_label: string; reason: string | null; created_at: string };
type Transition = { action_key: string; action_label: string; requires_reason: boolean };

const statusColors: Record<string, string> = {
    open: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    waiting_verification: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};
const statusLabels: Record<string, string> = { open: 'Open', in_progress: 'In Progress', waiting_verification: 'Waiting Verification', closed: 'Closed', rejected: 'Rejected' };
const actionColors: Record<string, string> = { start: 'bg-indigo-600 hover:bg-indigo-700', submit_verification: 'bg-blue-600 hover:bg-blue-700', verify_close: 'bg-green-600 hover:bg-green-700', reject: 'bg-red-600 hover:bg-red-700', restart: 'bg-yellow-600 hover:bg-yellow-700' };
const actionRouteMap: Record<string, string> = { start: 'start', submit_verification: 'submit_verification', verify_close: 'verify_close', reject: 'reject', restart: 'restart' };

export default function Show({ action, comments, activities, workflowHistory, availableTransitions, auth }: PageProps<{
    action: CapaAction; comments: Comment[]; activities: Activity[]; workflowHistory: WorkflowHistory[]; availableTransitions: Transition[];
}>) {
    const permissions = new Set(auth.permissions ?? []);
    const [showModal, setShowModal] = useState(false);
    const [modalAction, setModalAction] = useState('');
    const [commentBody, setCommentBody] = useState('');
    const { data: modalData, setData: setModalData, post: postModal, processing } = useForm({ reason: '' });

    const isOverdue = action.due_date && new Date(action.due_date) < new Date() && !['closed', 'rejected'].includes(action.status);
    const canEdit = ['open', 'in_progress', 'rejected'].includes(action.status) && permissions.has('capa.actions.update');

    function doAction(t: Transition) {
        if (t.requires_reason) { setModalAction(t.action_key); setShowModal(true); return; }
        router.post(route(`capa.actions.${actionRouteMap[t.action_key] ?? t.action_key}`, action.id));
    }
    function submitModal(e: FormEvent) { e.preventDefault(); postModal(route(`capa.actions.${actionRouteMap[modalAction] ?? modalAction}`, action.id), { onSuccess: () => setShowModal(false) }); }
    function submitComment(e: FormEvent) { e.preventDefault(); if (!commentBody.trim()) return; router.post('/core/comments', { module_name: 'capa', reference_id: action.id, body: commentBody }, { onSuccess: () => setCommentBody('') }); }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{action.action_number}</h2>}>
            <Head title={`CAPA ${action.action_number}`} />
            <div className="py-12"><div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="text-2xl font-bold text-gray-900 dark:text-gray-100">{action.action_number}</span>
                        <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusColors[action.status] ?? ''}`}>{statusLabels[action.status] ?? action.status}</span>
                        {isOverdue && <span className="inline-flex rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-800">OVERDUE</span>}
                        {action.priority && <span className="inline-flex rounded-full px-3 py-1 text-sm font-semibold" style={{ backgroundColor: `${action.priority.color}20`, color: action.priority.color }}>{action.priority.name}</span>}
                    </div>
                    <h1 className="mt-3 text-xl font-semibold text-gray-900 dark:text-gray-100">{action.title}</h1>
                    <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span>PIC: {action.assignedTo?.name ?? '-'}</span><span>Assigned by: {action.assignedBy?.name ?? '-'}</span>
                        <span>Site: {action.site?.name ?? '-'}</span>{action.due_date && <span className={isOverdue ? 'font-semibold text-red-600' : ''}>Due: {new Date(action.due_date).toLocaleDateString('id-ID')}</span>}
                        <span>Source: {action.source_module ?? '-'}</span><span>Type: {action.source_type ?? '-'}</span>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
<>                    <Link href={route('capa.actions.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Kembali</Link>
                        <DeleteWithConfirm
                            routeName="capa.actions.destroy"
                            id={action.id}
                            permission="capa.actions.delete"
                            itemLabel={action.action_number}
                            redirectTo="capa.actions.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
                    {canEdit && <Link href={route('capa.actions.edit', action.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Edit</Link>}
                    {availableTransitions.map((t) => {
                        const permKey = t.action_key === 'start' || t.action_key === 'restart' ? 'update' : t.action_key === 'submit_verification' ? 'submit' : t.action_key === 'verify_close' ? 'close' : t.action_key === 'reject' ? 'reject' : 'update';
                        if (!permissions.has(`capa.actions.${permKey}`)) return null;
                        return <button key={t.action_key} onClick={() => doAction(t)} className={`rounded-md px-4 py-2 text-sm font-medium text-white ${actionColors[t.action_key] ?? 'bg-gray-600 hover:bg-gray-700'}`}>{t.action_label}</button>;
                    })}
                </div>
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Deskripsi</h3>
                    <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{action.description}</p>
                </div>
                {action.verification_note && (
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Catatan Verifikasi</h3>
                        <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{action.verification_note}</p>
                        {action.verifiedBy && <p className="mt-2 text-sm text-gray-500">Diverifikasi oleh: {action.verifiedBy.name} pada {action.verified_at ? new Date(action.verified_at).toLocaleString('id-ID') : '-'}</p>}
                    </div>
                )}
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Status Timeline</h3>
                    <div className="space-y-3">{workflowHistory.map((h) => (
                        <div key={h.id} className="flex items-start gap-3 text-sm">
                            <div className="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-600" />
                            <div><span className="font-medium text-gray-900 dark:text-gray-100">{h.action_label}</span><span className="text-gray-500"> — {new Date(h.created_at).toLocaleString('id-ID')}</span>{h.reason && <p className="text-gray-500">{h.reason}</p>}</div>
                        </div>
                    ))}</div>
                </div>
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Comments</h3>
                    <form onSubmit={submitComment} className="mb-4 flex gap-2">
                        <input type="text" value={commentBody} onChange={(e) => setCommentBody(e.target.value)} placeholder="Tulis komentar..." className="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                        <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Kirim</button>
                    </form>
                    <div className="space-y-3">{comments.map((c) => (
                        <div key={c.id} className="border-b border-gray-100 pb-3 dark:border-gray-700">
                            <div className="flex items-center gap-2 text-sm"><span className="font-medium text-gray-900 dark:text-gray-100">{c.author?.name ?? 'Unknown'}</span><span className="text-gray-400">{new Date(c.created_at).toLocaleString('id-ID')}</span></div>
                            <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">{c.body}</p>
                        </div>
                    ))}</div>
                </div>
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">Activity Log</h3>
                    <div className="space-y-2">{activities.map((a) => (
                        <div key={a.id} className="text-sm text-gray-500 dark:text-gray-400"><span className="text-gray-400">{new Date(a.created_at).toLocaleString('id-ID')}</span> — {a.description ?? a.event} {a.actor_name && <span className="text-gray-400">({a.actor_name})</span>}</div>
                    ))}</div>
                </div>
            </div></div>
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{availableTransitions.find(t => t.action_key === modalAction)?.action_label ?? 'Konfirmasi'}</h3>
                        <form onSubmit={submitModal}>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan *</label>
                            <textarea value={modalData.reason} onChange={(e) => setModalData('reason', e.target.value)} rows={3} autoFocus className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                            <div className="mt-4 flex justify-end gap-2">
                                <button type="button" onClick={() => setShowModal(false)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</button>
                                <button type="submit" disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Konfirmasi</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
