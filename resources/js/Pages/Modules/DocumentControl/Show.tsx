import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type UserRef = { id: number; name: string; email?: string };
type DocumentItem = {
    id: number; document_number: string; title: string | null; type: string | null; version: string | null; revision_notes: string | null;
    effective_date: string | null; review_date: string | null; expiry_date: string | null; status: string; is_confidential: boolean;
    department?: { name: string } | null; owner?: UserRef | null; approver?: UserRef | null;
};
type FileItem = { id: number; original_name: string; mime_type: string; size: number; created_at: string };
type Review = { id: number; decision: string; review_notes: string | null; review_date: string | null; created_at: string; reviewer?: UserRef | null };
type Comment = { id: number; body: string; created_at: string; author?: UserRef | null };
type Activity = { id: number; event: string; description: string | null; actor_name: string | null; created_at: string };
type History = { id: number; action_label: string; from_status: string | null; to_status: string; reason: string | null; created_at: string };
type Ability = { update: boolean; submit_review: boolean; approve: boolean; make_effective: boolean; obsolete: boolean; revise: boolean; comment: boolean; download_file: boolean };

const statusStyle: Record<string, string> = { draft: 'bg-slate-100 text-slate-700', review: 'bg-blue-100 text-blue-700', approved: 'bg-amber-100 text-amber-800', effective: 'bg-emerald-100 text-emerald-700', obsolete: 'bg-red-100 text-red-700', rejected: 'bg-rose-100 text-rose-700' };
const statusLabel: Record<string, string> = { draft: 'Draft', review: 'Dalam Review', approved: 'Disetujui', effective: 'Efektif', obsolete: 'Obsolete', rejected: 'Ditolak' };

export default function Show({ document, files, reviews, comments, activities, workflowHistory, can }: PageProps<{
    document: DocumentItem; files: FileItem[]; reviews: Review[]; comments: Comment[]; activities: Activity[]; workflowHistory: History[]; can: Ability;
}>) {
    const [modal, setModal] = useState<'submitReview' | 'approve' | 'makeEffective' | 'obsolete' | 'reject' | null>(null);
    const [comment, setComment] = useState('');
    const { data, setData, post, processing, errors, reset } = useForm({ reason: '', review_notes: '', effective_date: '' });

    function simpleAction(action: 'revise') { router.post(route(`document.control.${action}`, document.id)); }
    function submitAction(event: FormEvent) {
        event.preventDefault();
        if (!modal) return;
        post(route(`document.control.${modal}`, document.id), { onSuccess: () => { setModal(null); reset(); } });
    }
    function submitComment(event: FormEvent) {
        event.preventDefault();
        if (!comment.trim()) return;
        router.post(route('document.control.comments.store', document.id), { body: comment }, { onSuccess: () => setComment('') });
    }
    const formatDate = (value: string | null) => value ? new Date(value).toLocaleDateString('id-ID') : '-';
    const formatBytes = (bytes: number) => bytes < 1024 * 1024 ? `${Math.ceil(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">{document.document_number}</h2>}>
            <Head title={document.document_number} />
            <div className="py-10"><div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section className="rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-900 p-6 text-white shadow-lg"><div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><div className="flex flex-wrap items-center gap-2"><span className="rounded-md bg-white/10 px-2.5 py-1 text-sm font-bold">{document.document_number}</span><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[document.status]}`}>{statusLabel[document.status]}</span><span className="rounded-full bg-indigo-400/20 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-100">{document.type || '-'} · v{document.version || '-'}</span>{document.is_confidential && <span className="rounded-full bg-purple-400/20 px-2.5 py-1 text-xs font-semibold text-purple-100">🔒 Rahasia</span>}</div><h1 className="mt-4 text-2xl font-bold">{document.title || 'Belum diberi judul'}</h1><p className="mt-2 text-sm text-slate-300">Owner: {document.owner?.name ?? '-'} · Department: {document.department?.name ?? 'Lintas department'}</p></div><Link href={route('document.control.index')} className="rounded-lg border border-white/30 px-4 py-2 text-center text-sm font-semibold hover:bg-white/10">Kembali</Link></div></section>

                <div className="flex flex-wrap gap-2">
                    {can.update && <Link href={route('document.control.edit', document.id)} className="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300">Edit</Link>}
                    {can.submit_review && <button onClick={() => setModal('submitReview')} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Submit Review</button>}
                    {can.approve && <><button onClick={() => setModal('approve')} className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button><button onClick={() => setModal('reject')} className="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Reject</button></>}
                    {can.make_effective && <button onClick={() => setModal('makeEffective')} className="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Make Effective</button>}
                    {can.obsolete && <button onClick={() => setModal('obsolete')} className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Mark Obsolete</button>}
                    {can.revise && <button onClick={() => simpleAction('revise')} className="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Kembali ke Draft</button>}
                </div>

                <div className="grid gap-6 lg:grid-cols-3"><div className="space-y-6 lg:col-span-2">
                    <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Kontrol Dokumen</h3><dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2"><div><dt className="text-slate-500">Tanggal efektif</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(document.effective_date)}</dd></div><div><dt className="text-slate-500">Tanggal review</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(document.review_date)}</dd></div><div><dt className="text-slate-500">Tanggal kedaluwarsa</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(document.expiry_date)}</dd></div><div><dt className="text-slate-500">Approver</dt><dd className="mt-1 font-semibold dark:text-white">{document.approver?.name ?? '-'}</dd></div></dl>{document.revision_notes && <div className="mt-5 border-t border-slate-100 pt-5 dark:border-gray-700"><p className="text-sm font-medium text-slate-500">Catatan revisi</p><p className="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{document.revision_notes}</p></div>}</section>
                    <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">File Terkontrol</h3><div className="mt-4 space-y-3">{files.length === 0 ? <p className="text-sm text-slate-500">Belum ada file.</p> : files.map((file) => <div key={file.id} className="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-3 dark:border-gray-700"><div className="min-w-0"><p className="truncate text-sm font-semibold text-slate-800 dark:text-white">{file.original_name}</p><p className="text-xs text-slate-500">{formatBytes(file.size)} · {new Date(file.created_at).toLocaleString('id-ID')}</p></div>{can.download_file ? <a href={route('document.control.files.download', [document.id, file.id])} className="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Download</a> : <span className="text-xs font-medium text-purple-600">Akses dibatasi</span>}</div>)}</div></section>
                    <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Review History</h3><div className="mt-4 space-y-3">{reviews.length === 0 ? <p className="text-sm text-slate-500">Belum ada review cycle.</p> : reviews.map((review) => <div key={review.id} className="rounded-lg border border-slate-200 p-4 dark:border-gray-700"><div className="flex items-center justify-between"><span className="text-sm font-semibold capitalize text-slate-800 dark:text-white">{review.decision}</span><span className="text-xs text-slate-400">{new Date(review.created_at).toLocaleString('id-ID')}</span></div><p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{review.review_notes ?? 'Tanpa catatan.'}</p><p className="mt-2 text-xs text-slate-400">Reviewer: {review.reviewer?.name ?? 'Belum ditentukan'}</p></div>)}</div></section>
                </div><aside className="space-y-6">
                    <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Workflow Timeline</h3><div className="mt-4 space-y-4">{workflowHistory.map((history) => <div key={history.id} className="flex gap-3"><span className="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-500" /><div><p className="text-sm font-semibold text-slate-800 dark:text-white">{history.action_label}</p><p className="text-xs text-slate-400">{new Date(history.created_at).toLocaleString('id-ID')}</p>{history.reason && <p className="mt-1 text-xs text-slate-500">{history.reason}</p>}</div></div>)}</div></section>
                    <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Komentar</h3>{can.comment && <form onSubmit={submitComment} className="mt-4"><textarea value={comment} onChange={(e) => setComment(e.target.value)} rows={3} placeholder="Tulis komentar..." className="w-full rounded-lg border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" /><button className="mt-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Kirim</button></form>}<div className="mt-4 space-y-3">{comments.map((item) => <div key={item.id} className="border-t border-slate-100 pt-3 dark:border-gray-700"><p className="text-xs font-semibold text-slate-700 dark:text-slate-200">{item.author?.name ?? 'Unknown'} <span className="font-normal text-slate-400">· {new Date(item.created_at).toLocaleString('id-ID')}</span></p><p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{item.body}</p></div>)}</div></section>
                    <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Activity Log</h3><div className="mt-4 space-y-3">{activities.map((item) => <div key={item.id} className="text-xs text-slate-500"><p>{item.description ?? item.event}</p><p className="mt-1 text-slate-400">{new Date(item.created_at).toLocaleString('id-ID')} {item.actor_name && `· ${item.actor_name}`}</p></div>)}</div></section>
                </aside></div>
            </div></div>

            {modal && <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"><div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800"><h3 className="text-lg font-semibold text-slate-900 dark:text-white">Konfirmasi {modal}</h3><form onSubmit={submitAction} className="mt-4 space-y-4">{['obsolete', 'reject'].includes(modal) ? <div><label className="text-sm font-medium dark:text-slate-300">Alasan *</label><textarea autoFocus rows={4} value={data.reason} onChange={(e) => setData('reason', e.target.value)} className="mt-1 w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />{errors.reason && <p className="mt-1 text-sm text-red-600">{errors.reason}</p>}</div> : modal === 'makeEffective' ? <div><label className="text-sm font-medium dark:text-slate-300">Tanggal efektif</label><input type="date" value={data.effective_date} onChange={(e) => setData('effective_date', e.target.value)} className="mt-1 w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></div> : <div><label className="text-sm font-medium dark:text-slate-300">Catatan review</label><textarea rows={4} value={data.review_notes} onChange={(e) => setData('review_notes', e.target.value)} className="mt-1 w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" /></div>}<div className="flex justify-end gap-2"><button type="button" onClick={() => setModal(null)} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Batal</button><button disabled={processing} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Konfirmasi</button></div></form></div></div>}
        </AuthenticatedLayout>
    );
}
