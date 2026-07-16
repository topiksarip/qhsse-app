import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface Holder { id: number; name: string }

interface WfTransition { action_key: string; action_label: string; requires_reason: boolean }
interface WfHistory { id: number; from_status: string | null; to_status: string; action_label: string; actor_name: string; created_at: string; reason: string | null }
interface Activity { id: number; description: string; created_at: string }

interface IssuanceItem {
    id: number;
    item_number: string;
    serial_number: string | null;
    catalog?: { name: string };
    site: { name: string };
    area: { name: string } | null;
    department: { name: string } | null;
}

type Props = {
    issuance: {
        id: number;
        issue_number: string;
        status: string;
        quantity: number;
        holder_label: string;
        holder_type: string;
        holder: Holder | null;
        condition_out: string | null;
        condition_in: string | null;
        issue_date: string | null;
        requested_date: string | null;
        expected_return_date: string | null;
        returned_date: string | null;
        expiry_date: string | null;
        notes: string | null;
        item: IssuanceItem | null;
    };
    workflow: { current_status: string; history: WfHistory[]; available_transitions: WfTransition[] };
    activities: Activity[];
    can: { request: boolean; approve: boolean; issue: boolean; receive: boolean; inspect: boolean };
};

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString('id-ID') : '-');

export default function Show({ issuance, workflow, activities, can }: PageProps<Props>) {
    const [reasonVisible, setReasonVisible] = useState(false);
    const [reason, setReason] = useState('');
    const [pendingAction, setPendingAction] = useState<string | null>(null);

    const submitTransition = (action: string) => {
        if (action === 'return') {
            router.post(`/apd/issuances/${issuance.id}/process`, { action, condition_in: 'good' });
            return;
        }
        if (action === 'dispose' || action === 'reject') {
            if (!reason.trim()) {
                setPendingAction(action);
                setReasonVisible(true);
                return;
            }
            router.post(`/apd/issuances/${issuance.id}/process`, { action, reason });
            return;
        }
        router.post(`/apd/issuances/${issuance.id}/${action}`, {});
    };

    const confirmReason = (e: FormEvent) => {
        e.preventDefault();
        if (pendingAction) {
            router.post(`/apd/issuances/${issuance.id}/process`, { action: pendingAction, reason });
        }
    };

    const holderName = issuance.holder?.name ?? issuance.holder_label;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/issuances" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Penugasan
                    </Link>
                    <h2 className="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{issuance.issue_number}</h2>
                </div>
            }
        >
            <Head title={`APD ${issuance.issue_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-2">
                        {workflow.available_transitions.map((t) => (
                            <PrimaryButton key={t.action_key} size="sm" onClick={() => submitTransition(t.action_key)}>
                                {t.action_label}
                            </PrimaryButton>
                        ))}
                        {can.issue && issuance.status === 'draft' && (
                            <PrimaryButton size="sm" onClick={() => submitTransition('issue')}>Issue Langsung</PrimaryButton>
                        )}
                        {can.inspect && issuance.status === 'issued' && issuance.item && (
                            <PrimaryButton size="sm" href={`/apd/inspections/create?apd_item_id=${issuance.item.id}`}>Inspeksi</PrimaryButton>
                        )}
                        <SecondaryButton size="sm" href="/apd/issuances">Tutup</SecondaryButton>
                    </div>

                    {reasonVisible && (
                        <form onSubmit={confirmReason} className="space-y-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                Alasan ({pendingAction === 'reject' ? 'tolak' : 'musnahkan'})
                            </label>
                            <textarea className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" rows={3} value={reason} onChange={(e) => setReason(e.target.value)} required />
                            <div className="flex gap-2">
                                <PrimaryButton size="sm" type="submit">Kirim</PrimaryButton>
                                <SecondaryButton size="sm" onClick={() => { setReasonVisible(false); setPendingAction(null); }}>Batal</SecondaryButton>
                            </div>
                        </form>
                    )}

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-2">
                            <h3 className="mb-4 text-lg font-semibold text-slate-800 dark:text-white">Detail Penugasan</h3>
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div><dt className="text-gray-500">Status</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.status}</dd></div>
                                <div><dt className="text-gray-500">Jumlah</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.quantity}</dd></div>
                                <div><dt className="text-gray-500">Item</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.item?.item_number ?? '-'}</dd></div>
                                <div><dt className="text-gray-500">Katalog</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.item?.catalog?.name ?? '-'}</dd></div>
                                <div><dt className="text-gray-500">Pemegang</dt><dd className="text-slate-800 dark:text-slate-100">{holderName} ({issuance.holder_label})</dd></div>
                                <div><dt className="text-gray-500">Lokasi Item</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.item?.site?.name ?? '-'}{issuance.item?.department?.name ? ` / ${issuance.item.department.name}` : ''}</dd></div>
                                <div><dt className="text-gray-500">Kondisi Issue</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.condition_out ?? '-'}</dd></div>
                                <div><dt className="text-gray-500">Kondisi Kembali</dt><dd className="text-slate-800 dark:text-slate-100">{issuance.condition_in ?? '-'}</dd></div>
                                <div><dt className="text-gray-500">Tgl Request</dt><dd className="text-slate-800 dark:text-slate-100">{formatDate(issuance.requested_date)}</dd></div>
                                <div><dt className="text-gray-500">Tgl Issue</dt><dd className="text-slate-800 dark:text-slate-100">{formatDate(issuance.issue_date)}</dd></div>
                                <div><dt className="text-gray-500">Rencana Kembali</dt><dd className="text-slate-800 dark:text-slate-100">{formatDate(issuance.expected_return_date)}</dd></div>
                                <div><dt className="text-gray-500">Kedaluwarsa</dt><dd className="text-slate-800 dark:text-slate-100">{formatDate(issuance.expiry_date)}</dd></div>
                                <div><dt className="text-gray-500">Tgl Kembali</dt><dd className="text-slate-800 dark:text-slate-100">{formatDate(issuance.returned_date)}</dd></div>
                            </dl>
                            {issuance.notes && (
                                <div className="mt-4">
                                    <dt className="text-sm text-gray-500">Catatan</dt>
                                    <dd className="mt-1 text-sm text-slate-800 dark:text-slate-100">{issuance.notes}</dd>
                                </div>
                            )}
                        </div>

                        <div className="space-y-6">
                            <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <h3 className="mb-4 text-lg font-semibold text-slate-800 dark:text-white">Workflow History</h3>
                                <ul className="space-y-3 text-sm">
                                    {workflow.history.length === 0 ? (
                                        <li className="text-gray-500">Belum ada transisi.</li>
                                    ) : (
                                        workflow.history.map((h) => (
                                            <li key={h.id} className="border-l-2 border-blue-200 pl-3">
                                                <p className="text-slate-700 dark:text-slate-200">
                                                    {h.action_label} → <span className="font-medium">{h.to_status}</span>
                                                </p>
                                                <p className="text-xs text-gray-400">{h.actor_name} • {formatDate(h.created_at)}</p>
                                                {h.reason && <p className="mt-1 text-xs text-red-500">{h.reason}</p>}
                                            </li>
                                        ))
                                    )}
                                </ul>
                            </div>
                            <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <h3 className="mb-4 text-lg font-semibold text-slate-800 dark:text-white">Aktivitas</h3>
                                <ul className="space-y-2 text-sm">
                                    {activities.length === 0 ? (
                                        <li className="text-gray-500">Belum ada aktivitas.</li>
                                    ) : (
                                        activities.map((a) => (
                                            <li key={a.id} className="border-l-2 border-gray-200 pl-3">
                                                <p className="text-slate-700 dark:text-slate-200">{a.description}</p>
                                                <p className="text-xs text-gray-400">{formatDate(a.created_at)}</p>
                                            </li>
                                        ))
                                    )}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
