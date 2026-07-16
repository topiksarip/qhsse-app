import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { FormEvent, useState } from 'react';

type Option = { id: number; name: string };
type UserRef = { id: number; name: string };
type Audit = {
    id: number;
    audit_number: string;
    title: string | null;
    audit_type: string | null;
    status: string;
    scope: string | null;
    summary: string | null;
    scheduled_date: string | null;
    start_date: string | null;
    close_date: string | null;
    created_at: string;
    department?: Option | null;
    lead_auditor?: UserRef | null;
};
type Finding = {
    id: number;
    finding_number: string;
    description: string;
    classification: string;
    status: string;
    recommendation: string | null;
    due_date: string | null;
    closed_at: string | null;
    created_at: string;
};
type EvidenceFile = { id: number; original_name: string; mime_type: string; size: number; created_at: string };
type Comment = { id: number; body: string; created_at: string; author?: UserRef | null };
type Activity = { id: number; event: string; description: string | null; actor_name: string | null; created_at: string };
type History = { id: number; action_label: string; from_status: string | null; to_status: string; reason: string | null; created_at: string };
type Ability = {
    update: boolean;
    start: boolean;
    generate_report: boolean;
    close: boolean;
    create_finding: boolean;
    close_finding: boolean;
    comment: boolean;
    upload_file: boolean;
    download_file: boolean;
};

const statusStyle: Record<string, string> = {
    planned: 'bg-blue-100 text-blue-700', in_progress: 'bg-yellow-100 text-yellow-800',
    report_ready: 'bg-purple-100 text-purple-700', closed: 'bg-emerald-100 text-emerald-700',
};
const statusLabel: Record<string, string> = {
    planned: 'Direncanakan', in_progress: 'Berlangsung', report_ready: 'Laporan Siap', closed: 'Ditutup',
};
const typeLabel: Record<string, string> = {
    internal: 'Internal', external: 'Eksternal', supplier: 'Pemasok', regulatory: 'Regulator',
};
const classificationStyle: Record<string, string> = {
    major_nc: 'bg-red-100 text-red-700', minor_nc: 'bg-amber-100 text-amber-700', observation: 'bg-blue-100 text-blue-700', ofi: 'bg-cyan-100 text-cyan-700',
};
const classificationLabel: Record<string, string> = {
    major_nc: 'Mayor', minor_nc: 'Minor', observation: 'Observasi', ofi: 'Peluang Perbaikan',
};
const findingStatusStyle: Record<string, string> = {
    open: 'bg-slate-100 text-slate-700', closed: 'bg-emerald-100 text-emerald-700',
};
const findingStatusLabel: Record<string, string> = {
    open: 'Terbuka', closed: 'Ditutup',
};

export default function Show({ audit, findings, evidenceFiles, comments, activities, workflowHistory, can }: PageProps<{
    audit: Audit; findings: Finding[]; evidenceFiles: EvidenceFile[]; comments: Comment[]; activities: Activity[]; workflowHistory: History[]; can: Ability;
}>) {
    const [modal, setModal] = useState<'generateReport' | 'close' | null>(null);
    const [comment, setComment] = useState('');
    const [showFindingForm, setShowFindingForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ summary: '' });

    function startAudit() { router.post(route('audits.start', audit.id)); }
    function submitAction(event: FormEvent) {
        event.preventDefault();
        if (!modal) return;
        post(route(modal === 'generateReport' ? 'audits.generate-report' : 'audits.close', audit.id), {
            onSuccess: () => { setModal(null); reset(); },
        });
    }
    function submitComment(event: FormEvent) {
        event.preventDefault();
        if (!comment.trim()) return;
        router.post(route('audits.comment', audit.id), { body: comment }, { onSuccess: () => setComment('') });
    }
    function closeFinding(findingId: number) {
        router.post(route('audits.findings.close', [audit.id, findingId]));
    }

    function submitFinding(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        router.post(route('audits.findings.store', audit.id), Object.fromEntries(new FormData(form)), {
            onSuccess: () => { form.reset(); setShowFindingForm(false); },
        });
    }

    function submitEvidence(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const form = event.currentTarget;
        router.post(route('audits.files.store', audit.id), new FormData(form), {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    }

    const formatDate = (value: string | null) => value ? new Date(value).toLocaleDateString('id-ID') : '-';
    const formatBytes = (bytes: number) => bytes < 1024 * 1024 ? `${Math.ceil(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800 dark:text-slate-200">{audit.audit_number}</h2>}>
            <Head title={audit.audit_number} />
            <div className="py-10"><div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <section className="rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-900 p-6 text-white shadow-lg">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-md bg-white/10 px-2.5 py-1 text-sm font-bold">{audit.audit_number}</span>
                                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyle[audit.status] ?? ''}`}>{statusLabel[audit.status] ?? audit.status}</span>
                                <span className="rounded-full bg-indigo-400/20 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-100">{typeLabel[audit.audit_type ?? ''] ?? audit.audit_type ?? '-'}</span>
                            </div>
                            <h1 className="mt-4 text-2xl font-bold">{audit.title || 'Belum diberi judul'}</h1>
                            <p className="mt-2 text-sm text-slate-300">Auditor Utama: {audit.lead_auditor?.name ?? '-'} · Department: {audit.department?.name ?? 'Lintas department'}</p>
                        </div>
                        <Link href={route('audits.index')} className="rounded-lg border border-white/30 px-4 py-2 text-center text-sm font-semibold hover:bg-white/10">Kembali</Link>
                    </div>
                </section>

                {/* Workflow Actions */}
                <div className="flex flex-wrap gap-2">
<>                    {can.update && <Link href={route('audits.edit', audit.id)} className="rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 dark:bg-gray-700 dark:text-gray-200">Edit</Link>}
                        <DeleteWithConfirm
                            routeName="audits.destroy"
                            id={audit.id}
                            permission="audits.delete"
                            itemLabel={audit.audit_number}
                            redirectTo="audits.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
                    {can.start && <button onClick={startAudit} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Mulai Audit</button>}
                    {can.generate_report && <button onClick={() => setModal('generateReport')} className="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700">Buat Laporan</button>}
                    {can.close && <button onClick={() => setModal('close')} className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Tutup Audit</button>}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {/* Audit Info */}
                        <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Informasi Audit</h3>
                            <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                                <div><dt className="text-slate-500">Tanggal Jadwal</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(audit.scheduled_date)}</dd></div>
                                <div><dt className="text-slate-500">Tanggal Mulai</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(audit.start_date)}</dd></div>
                                <div><dt className="text-slate-500">Tanggal Tutup</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(audit.close_date)}</dd></div>
                                <div><dt className="text-slate-500">Dibuat</dt><dd className="mt-1 font-semibold dark:text-white">{formatDate(audit.created_at)}</dd></div>
                            </dl>
                            {audit.scope && <div className="mt-5 border-t border-slate-100 pt-5 dark:border-gray-700"><p className="text-sm font-medium text-slate-500">Ruang Lingkup</p><p className="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{audit.scope}</p></div>}
                            {audit.summary && <div className="mt-5 border-t border-slate-100 pt-5 dark:border-gray-700"><p className="text-sm font-medium text-slate-500">Ringkasan</p><p className="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{audit.summary}</p></div>}
                        </section>

                        {/* Findings */}
                        <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Temuan ({findings.length})</h3>
                                {can.create_finding && <button onClick={() => setShowFindingForm((visible) => !visible)} className="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{showFindingForm ? 'Batal' : 'Tambah Temuan'}</button>}
                            </div>
                            {showFindingForm && (
                                <form onSubmit={submitFinding} className="mt-4 grid gap-3 rounded-lg border border-indigo-100 bg-indigo-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/40 sm:grid-cols-2">
                                    <select name="classification" required className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Pilih klasifikasi</option>
                                        <option value="major_nc">Ketidaksesuaian Mayor</option>
                                        <option value="minor_nc">Ketidaksesuaian Minor</option>
                                        <option value="observation">Observasi</option>
                                        <option value="ofi">Peluang Perbaikan</option>
                                    </select>
                                    <input type="date" name="due_date" className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    <textarea name="description" required rows={3} placeholder="Deskripsi temuan" className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:col-span-2" />
                                    <textarea name="recommendation" rows={2} placeholder="Rekomendasi perbaikan" className="rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:col-span-2" />
                                    <button className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white sm:col-span-2">Simpan Temuan</button>
                                </form>
                            )}
                            <div className="mt-4 space-y-4">
                                {findings.length === 0 ? (
                                    <p className="text-sm text-slate-500">Belum ada temuan.</p>
                                ) : findings.map((finding) => (
                                    <div key={finding.id} className="rounded-lg border border-slate-200 p-4 dark:border-gray-700">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-bold text-slate-800 dark:text-white">{finding.finding_number}</span>
                                                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${classificationStyle[finding.classification] ?? 'bg-slate-100 text-slate-700'}`}>{classificationLabel[finding.classification] ?? finding.classification}</span>
                                                <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${findingStatusStyle[finding.status] ?? 'bg-slate-100 text-slate-700'}`}>{findingStatusLabel[finding.status] ?? finding.status}</span>
                                            </div>
                                            <div className="flex gap-2">
                                                {can.close_finding && finding.status === 'open' && <button onClick={() => closeFinding(finding.id)} className="rounded-md bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Tutup Temuan</button>}
                                            </div>
                                        </div>
                                        <p className="mt-3 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{finding.description}</p>
                                        {finding.recommendation && <div className="mt-3"><p className="text-xs font-medium text-slate-500">Rekomendasi</p><p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{finding.recommendation}</p></div>}
                                        <div className="mt-3 flex flex-wrap gap-4 text-xs text-slate-400">
                                            <span>Jatuh Tempo: {finding.due_date ? new Date(finding.due_date).toLocaleDateString('id-ID') : '-'}</span>
                                            <span>Dibuat: {new Date(finding.created_at).toLocaleDateString('id-ID')}</span>
                                            {finding.closed_at && <span>Ditutup: {new Date(finding.closed_at).toLocaleDateString('id-ID')}</span>}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        {/* Evidence Files */}
                        <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Bukti ({evidenceFiles.length})</h3>
                            {can.upload_file && (
                                <form onSubmit={submitEvidence} className="mt-4 flex flex-col gap-2 rounded-lg border border-dashed border-slate-300 p-4 sm:flex-row sm:items-center dark:border-gray-600">
                                    <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" className="min-w-0 flex-1 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:text-slate-300" />
                                    <button className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Unggah</button>
                                </form>
                            )}
                            <div className="mt-4 space-y-3">
                                {evidenceFiles.length === 0 ? (
                                    <p className="text-sm text-slate-500">Belum ada bukti.</p>
                                ) : evidenceFiles.map((file) => (
                                    <div key={file.id} className="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-3 dark:border-gray-700">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-slate-800 dark:text-white">{file.original_name}</p>
                                            <p className="text-xs text-slate-500">{formatBytes(file.size)} · {new Date(file.created_at).toLocaleString('id-ID')}</p>
                                        </div>
                                        {can.download_file ? (
                                            <a href={route('audits.files.download', [audit.id, file.id])} className="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Download</a>
                                        ) : <span className="text-xs font-medium text-purple-600">Akses dibatasi</span>}
                                    </div>
                                ))}
                            </div>
                        </section>
                    </div>

                    {/* Sidebar */}
                    <aside className="space-y-6">
                        {/* Workflow Timeline */}
                        <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Riwayat Workflow</h3>
                            <div className="mt-4 space-y-4">
                                {workflowHistory.map((history) => (
                                    <div key={history.id} className="flex gap-3">
                                        <span className="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-indigo-500" />
                                        <div>
                                            <p className="text-sm font-semibold text-slate-800 dark:text-white">{history.action_label}</p>
                                            <p className="text-xs text-slate-400">{new Date(history.created_at).toLocaleString('id-ID')}</p>
                                            {history.reason && <p className="mt-1 text-xs text-slate-500">{history.reason}</p>}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>

                        {/* Comments */}
                        <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Komentar</h3>
                            {can.comment && (
                                <form onSubmit={submitComment} className="mt-4">
                                    <textarea value={comment} onChange={(e) => setComment(e.target.value)} rows={3} placeholder="Tulis komentar..." className="w-full rounded-lg border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    <button className="mt-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Kirim</button>
                                </form>
                            )}
                            <div className="mt-4 space-y-3">
                                {comments.map((item) => (
                                    <div key={item.id} className="border-t border-slate-100 pt-3 dark:border-gray-700">
                                        <p className="text-xs font-semibold text-slate-700 dark:text-slate-200">{item.author?.name ?? 'Unknown'} <span className="font-normal text-slate-400">· {new Date(item.created_at).toLocaleString('id-ID')}</span></p>
                                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{item.body}</p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        {/* Activity Log */}
                        <section className="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Log Aktivitas</h3>
                            <div className="mt-4 space-y-3">
                                {activities.map((item) => (
                                    <div key={item.id} className="text-xs text-slate-500">
                                        <p>{item.description ?? item.event}</p>
                                        <p className="mt-1 text-slate-400">{new Date(item.created_at).toLocaleString('id-ID')} {item.actor_name && `· ${item.actor_name}`}</p>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </aside>
                </div>
            </div></div>

            {/* Generate Report / Close Modal */}
            {modal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="text-lg font-semibold text-slate-900 dark:text-white">{modal === 'generateReport' ? 'Buat Laporan' : 'Tutup Audit'}</h3>
                        <form onSubmit={submitAction} className="mt-4 space-y-4">
                            {modal === 'generateReport' && (
                                <div>
                                    <label className="text-sm font-medium dark:text-slate-300">Ringkasan *</label>
                                    <textarea autoFocus rows={5} value={data.summary} onChange={(e) => setData('summary', e.target.value)} className="mt-1 w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Ringkasan hasil audit..." />
                                    {errors.summary && <p className="mt-1 text-sm text-red-600">{errors.summary}</p>}
                                </div>
                            )}
                            {modal === 'close' && (
                                <p className="text-sm text-slate-600 dark:text-slate-300">Audit akan ditutup. Pastikan semua temuan sudah ditangani.</p>
                            )}
                            <div className="flex justify-end gap-2">
                                <button type="button" onClick={() => setModal(null)} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">Batal</button>
                                <button disabled={processing} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Konfirmasi</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

        </AuthenticatedLayout>
    );
}
