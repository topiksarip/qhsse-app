import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { PageProps, Permit, PermitChecklist, PermitWorker, PermitAsset, ValidityStatus } from '@/types';
import { FormEvent, useState } from 'react';
import PermitTypeBadge from '@/Components/Permit/PermitTypeBadge';
import StatusBadge from '@/Components/Permit/StatusBadge';
import RiskBadge from '@/Components/Permit/RiskBadge';
import ValidityBadge from '@/Components/Permit/ValidityBadge';
import ChecklistItem from '@/Components/Permit/ChecklistItem';
import CountdownTimer from '@/Components/Permit/CountdownTimer';
import WorkflowTimeline from '@/Components/Permit/WorkflowTimeline';

interface WorkflowStep {
    id: number;
    from_status: string | null;
    to_status: string;
    action_key: string;
    action_label: string;
    reason: string | null;
    actor_name: string | null;
    created_at: string;
}

interface AvailableAction {
    action_key: string;
    action_label: string;
    requires_reason: boolean;
}

interface ChecklistProgress {
    total: number;
    signed: number;
    all_signed: boolean;
}

interface ShowProps extends PageProps {
    permit: Permit & {
        checklists: PermitChecklist[];
        permit_workers?: PermitWorker[];
        permit_assets?: PermitAsset[];
    };
    workflow: {
        current_status: string | null;
        available_transitions: unknown[];
        history: WorkflowStep[];
    };
    availableActions: AvailableAction[];
    checklistProgress: ChecklistProgress;
}

function validityOf(p: Permit): ValidityStatus {
    const now = new Date();
    const start = new Date(p.start_datetime);
    const end = new Date(p.end_datetime);
    if (p.status !== 'active') return 'not_started';
    if (now > end) return 'expired';
    if (end.getTime() - now.getTime() <= 24 * 3600 * 1000) return 'expiring_soon';
    return 'active';
}

function fmt(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID');
}

const actionColors: Record<string, string> = {
    submit: 'bg-blue-600 hover:bg-blue-700',
    review: 'bg-yellow-600 hover:bg-yellow-700',
    approve: 'bg-green-600 hover:bg-green-700',
    activate: 'bg-green-600 hover:bg-green-700',
    close: 'bg-red-600 hover:bg-red-700',
    reject: 'bg-red-600 hover:bg-red-700',
};

export default function Show({ auth, permit, workflow, availableActions, checklistProgress }: ShowProps) {
    const permissions = new Set(auth.permissions ?? []);
    const [showModal, setShowModal] = useState(false);
    const [modalAction, setModalAction] = useState<AvailableAction | null>(null);
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);

    const validity = validityOf(permit);
    const canUpdate = permissions.has('permit.work.update') && permit.status === 'draft';
    const canSignChecklist = permissions.has('permit.work.checklist') && permit.status !== 'closed' && permit.status !== 'rejected';

    function doAction(a: AvailableAction) {
        if (a.requires_reason) {
            setModalAction(a);
            setShowModal(true);
            return;
        }
        router.post(route('permit.work.transition', permit.id), { action: a.action_key });
    }

    function submitModal(e: FormEvent) {
        e.preventDefault();
        if (!modalAction) return;
        setProcessing(true);
        router.post(
            route('permit.work.transition', permit.id),
            { action: modalAction.action_key, reason },
            {
                onSuccess: () => { setShowModal(false); setReason(''); setProcessing(false); },
                onFinish: () => setProcessing(false),
            },
        );
    }

    const pct = checklistProgress.total > 0
        ? Math.round((checklistProgress.signed / checklistProgress.total) * 100)
        : 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link href={route('permit.work.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali</Link>
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{permit.permit_number}</h2>
                            <PermitTypeBadge type={permit.type} />
                            <StatusBadge status={permit.status} />
                            <ValidityBadge status={validity} />
                        </div>
                        <p className="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">{permit.title}</p>
                    </div>
                    <div className="flex gap-2">
                        {canUpdate && (
<>                            <Link href={route('permit.work.edit', permit.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">✏ Edit</Link>
                        <DeleteWithConfirm
                            routeName="permit.work.destroy"
                            id={permit.id}
                            permission="permit.work.delete"
                            itemLabel={permit.permit_number}
                            redirectTo="permit.work.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Izin Kerja ${permit.permit_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Informasi Izin */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">INFORMASI IZIN</h3>
                        <dl className="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2 dark:border-gray-700">
                            <div><dt className="text-xs text-gray-500">Nomor Izin</dt><dd className="font-mono text-sm text-gray-900 dark:text-gray-100">{permit.permit_number}</dd></div>
                            <div><dt className="text-xs text-gray-500">Jenis</dt><dd className="text-sm"><PermitTypeBadge type={permit.type} /></dd></div>
                            <div className="md:col-span-2"><dt className="text-xs text-gray-500">Judul</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.title}</dd></div>
                            <div className="md:col-span-2"><dt className="text-xs text-gray-500">Deskripsi</dt><dd className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{permit.description || '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Risk Level</dt><dd className="text-sm"><RiskBadge level={permit.risk_level} /></dd></div>
                            <div><dt className="text-xs text-gray-500">JSA Reference</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.jsa_reference || '-'}</dd></div>
                        </dl>
                    </div>

                    {/* Lokasi & Pekerja */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">LOKASI & PEKERJA</h3>
                        <dl className="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2 dark:border-gray-700">
                            <div><dt className="text-xs text-gray-500">Site</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.site?.name ?? '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Area</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.area?.name ?? '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Department</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.department?.name ?? '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Contractor</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.contractor?.name ?? '-'}</dd></div>
                            <div className="md:col-span-2"><dt className="text-xs text-gray-500">Lokasi Kerja</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.work_location}</dd></div>
                            <div className="md:col-span-2"><dt className="text-xs text-gray-500">Deskripsi Pekerjaan</dt><dd className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{permit.work_description || '-'}</dd></div>
                        </dl>
                    </div>

                    {/* Checklist */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">CHECKLIST KESELAMATAN</h3>
                        <div className="mb-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Progress: {checklistProgress.signed}/{checklistProgress.total} items di-sign
                            </p>
                            <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div className="h-full bg-green-500" style={{ width: `${pct}%` }} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            {permit.checklists.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada item checklist.</p>
                            ) : permit.checklists.map((item) => (
                                <ChecklistItem key={item.id} item={item} permitId={permit.id} canSign={canSignChecklist} />
                            ))}
                        </div>
                        <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">⚠ Semua checklist harus di-sign sebelum izin dapat diaktifkan.</p>
                    </div>

                    {/* Alat & Pekerja */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">ALAT & PEKERJA</h3>
                        {(!permit.permit_workers || permit.permit_workers.length === 0) && (!permit.permit_assets || permit.permit_assets.length === 0) ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada alat atau pekerja dicatat.</p>
                        ) : (
                            <div className="space-y-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div>
                                    <h4 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Pekerja</h4>
                                    {permit.permit_workers && permit.permit_workers.length > 0 ? (
                                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {permit.permit_workers.map((w) => (
                                                <li key={w.id} className="flex items-center justify-between py-2">
                                                    <span className="text-gray-800 dark:text-gray-200">
                                                        {w.employee?.employee_no ? `${w.employee.employee_no} — ` : ''}{w.employee?.name}
                                                    </span>
                                                    {Array.isArray(w.role) && w.role.length > 0 ? (
                                                        <span className="flex flex-wrap gap-1">
                                                            {w.role.map((r, i) => (
                                                                <span key={i} className="rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{r}</span>
                                                            ))}
                                                        </span>
                                                    ) : w.role ? (
                                                        <span className="rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{String(w.role)}</span>
                                                    ) : null}
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Tidak ada pekerja.</p>
                                    )}
                                </div>
                                <div>
                                    <h4 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Alat / Peralatan</h4>
                                    {permit.permit_assets && permit.permit_assets.length > 0 ? (
                                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {permit.permit_assets.map((a) => (
                                                <li key={a.id} className="flex items-center justify-between py-2">
                                                    <span className="text-gray-800 dark:text-gray-200">
                                                        {a.asset?.asset_number} — {a.asset?.name}
                                                    </span>
                                                    {Array.isArray(a.role) && a.role.length > 0 ? (
                                                        <span className="flex flex-wrap gap-1">
                                                            {a.role.map((r, i) => (
                                                                <span key={i} className="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{r}</span>
                                                            ))}
                                                        </span>
                                                    ) : a.role ? (
                                                        <span className="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{String(a.role)}</span>
                                                    ) : null}
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Tidak ada alat.</p>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Approval & Workflow */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">ALUR APPROVAL</h3>
                        <dl className="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2 dark:border-gray-700">
                            <div><dt className="text-xs text-gray-500">Requester</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.creator?.name ?? '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Approved By</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.approver?.name ?? '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Approved At</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{fmt(permit.approved_at)}</dd></div>
                            <div><dt className="text-xs text-gray-500">Closed By</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{permit.closer?.name ?? '-'}</dd></div>
                        </dl>
                        <div className="mt-4">
                            <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Workflow Timeline</p>
                            <WorkflowTimeline history={workflow.history} />
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {availableActions
                                .filter((a) => permissions.has(`permit.work.${a.action_key === 'activate' ? 'approve' : a.action_key === 'close' ? 'close' : a.action_key}`))
                                .map((a) => (
                                    <button
                                        key={a.action_key}
                                        onClick={() => doAction(a)}
                                        className={`rounded-md px-4 py-2 text-sm font-medium text-white ${actionColors[a.action_key] ?? 'bg-gray-600 hover:bg-gray-700'}`}
                                    >
                                        {a.action_label}
                                    </button>
                                ))}
                        </div>
                    </div>

                    {showModal && modalAction && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={() => setShowModal(false)}>
                            <div className="w-full max-w-md rounded-lg bg-white p-6 shadow dark:bg-gray-800" onClick={(e) => e.stopPropagation()}>
                                <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">{modalAction.action_label}</h3>
                                <form onSubmit={submitModal} className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alasan <span className="text-red-500">*</span></label>
                                        <textarea value={reason} onChange={(e) => setReason(e.target.value)} rows={3} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Minimal 10 karakter" required />
                                    </div>
                                    <div className="flex justify-end gap-2">
                                        <button type="button" onClick={() => setShowModal(false)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</button>
                                        <button type="submit" disabled={processing || reason.trim().length < 10} className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">Kirim</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}