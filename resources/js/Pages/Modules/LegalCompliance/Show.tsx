import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { PageProps, LegalRegister, LegalObligation, LegalObligationFrequency } from '@/types';
import ComplianceStatusBadge from '@/Components/LegalCompliance/ComplianceStatusBadge';
import CategoryBadge from '@/Components/LegalCompliance/CategoryBadge';
import RegisterStatusBadge from '@/Components/LegalCompliance/RegisterStatusBadge';
import ObligationStatusBadge, { getObligationStatus } from '@/Components/LegalCompliance/ObligationStatusBadge';
import { useState } from 'react';

interface ShowProps extends PageProps {
    register: LegalRegister;
}

const frequencies: { value: LegalObligationFrequency; label: string }[] = [
    { value: 'monthly', label: 'Bulanan' },
    { value: 'quarterly', label: 'Triwulanan' },
    { value: 'annual', label: 'Tahunan' },
];

export default function Show({ auth, register }: ShowProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canUpdate = permissions.has('legal.register.update');
    const canCreateObligation = permissions.has('legal.obligations.create');
    const canUpdateObligation = permissions.has('legal.obligations.update');

    const [showAdd, setShowAdd] = useState(false);
    const [completeId, setCompleteId] = useState<number | null>(null);

    // Add obligation form
    const addForm = useForm<{ obligation_description: string; frequency: LegalObligationFrequency; last_completed: string; next_due: string }>({
        obligation_description: '',
        frequency: 'annual',
        last_completed: '',
        next_due: '',
    });

    // Complete obligation form
    const completeForm = useForm<{ last_completed: string }>({ last_completed: '' });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        addForm.post(route('legal.registers.obligations.store', register.id), {
            onSuccess: () => { setShowAdd(false); addForm.reset(); },
        });
    }

    function submitComplete(e: React.FormEvent) {
        e.preventDefault();
        if (completeId === null) return;
        completeForm.post(route('legal.registers.obligations.complete', [register.id, completeId]), {
            onSuccess: () => { setCompleteId(null); completeForm.reset(); },
        });
    }

    function deleteObligation(id: number) {
        if (!confirm('Hapus kewajiban ini?')) return;
        router.delete(route('legal.registers.obligations.destroy', [register.id, id]));
    }

    function deleteRegister() {
        if (!confirm(`Hapus register ${register.register_number}?`)) return;
        router.delete(route('legal.registers.destroy', register.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('legal.registers.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali ke Daftar</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">Legal &amp; Compliance Register</h2>
                </div>
            }
        >
            <Head title={`Legal ${register.register_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">{register.register_number}</span>
                                    <CategoryBadge category={register.category} />
                                    <ComplianceStatusBadge status={register.compliance_status} />
                                    <RegisterStatusBadge status={register.status} />
                                </div>
                                <h3 className="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">{register.title}</h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400">{register.regulation_name} ({register.regulation_number})</p>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Instansi: {register.issuing_body}</p>
                            </div>
                            {canUpdate && (
                                <div className="flex gap-2">
                                    <Link href={route('legal.registers.edit', register.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">✏ Edit</Link>
                                    <button onClick={deleteRegister} className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">🗑 Hapus</button>
                                </div>
                            )}
                        </div>

                        <dl className="mt-4 grid grid-cols-1 gap-3 border-t border-gray-200 pt-4 text-sm sm:grid-cols-2 dark:border-gray-700">
                            <Row label="Site" value={register.site?.name ?? '-'} />
                            <Row label="Department" value={register.department?.name ?? '-'} />
                            <Row label="Owner" value={register.owner?.name ?? '-'} />
                            <Row label="Dokumen" value={register.document ? `${register.document.doc_number} — ${register.document.title}` : '-'} />
                            <Row label="Review Berikutnya" value={register.next_review_date ?? '-'} />
                            <Row label="Status Kepatuhan" value={register.compliance_status_label ?? register.compliance_status} />
                            {register.notes && (
                                <div className="sm:col-span-2">
                                    <dt className="text-gray-500 dark:text-gray-400">Catatan</dt>
                                    <dd className="mt-1 whitespace-pre-wrap text-gray-900 dark:text-gray-100">{register.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {/* Obligations */}
                    <div className="mt-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kewajiban (Obligations)</h3>
                            {canCreateObligation && (
                                <button onClick={() => setShowAdd(true)} className="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">+ Tambah</button>
                            )}
                        </div>

                        {showAdd && (
                            <form onSubmit={submitAdd} className="mb-4 grid grid-cols-1 gap-3 rounded-md bg-gray-50 p-4 dark:bg-gray-900 md:grid-cols-2">
                                <input type="text" value={addForm.data.obligation_description} onChange={(e) => addForm.setData('obligation_description', e.target.value)} placeholder="Deskripsi kewajiban..." className="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm md:col-span-2" />
                                <select value={addForm.data.frequency} onChange={(e) => addForm.setData('frequency', e.target.value as LegalObligationFrequency)} className="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                    {frequencies.map((f) => <option key={f.value} value={f.value}>{f.label}</option>)}
                                </select>
                                <div className="flex gap-2">
                                    <input type="date" value={addForm.data.last_completed} onChange={(e) => addForm.setData('last_completed', e.target.value)} placeholder="Terakhir" className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" title="Terakhir dilaksanakan" />
                                    <input type="date" value={addForm.data.next_due} onChange={(e) => addForm.setData('next_due', e.target.value)} placeholder="Jatuh tempo" className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" title="Jatuh tempo berikutnya" />
                                </div>
                                <div className="flex gap-2 md:col-span-2">
                                    <button type="submit" disabled={addForm.processing} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">Simpan Kewajiban</button>
                                    <button type="button" onClick={() => setShowAdd(false)} className="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</button>
                                </div>
                            </form>
                        )}

                        {!register.obligations || register.obligations.length === 0 ? (
                            <p className="text-sm text-gray-500">Belum ada kewajiban.</p>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr className="text-left text-xs font-medium uppercase text-gray-500">
                                        <th className="py-2">Deskripsi</th>
                                        <th className="py-2">Frekuensi</th>
                                        <th className="py-2">Terakhir</th>
                                        <th className="py-2">Jatuh Tempo</th>
                                        <th className="py-2">Status</th>
                                        <th className="py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {register.obligations.map((o: LegalObligation) => (
                                        <tr key={o.id}>
                                            <td className="py-2 text-sm text-gray-900 dark:text-gray-100">{o.obligation_description}</td>
                                            <td className="py-2 text-sm text-gray-600 dark:text-gray-400">{o.frequency_label ?? o.frequency}</td>
                                            <td className="py-2 text-sm text-gray-600 dark:text-gray-400">{o.last_completed ?? '-'}</td>
                                            <td className="py-2 text-sm text-gray-600 dark:text-gray-400">{o.next_due ?? '-'}</td>
                                            <td className="py-2"><ObligationStatusBadge obligation={o} /></td>
                                            <td className="py-2 text-right text-sm">
                                                {canUpdateObligation && o.status === 'pending' && (
                                                    <button onClick={() => setCompleteId(o.id)} className="text-green-600 hover:underline dark:text-green-400">Selesai</button>
                                                )}
                                                {canUpdateObligation && (
                                                    <>{' · '}<button onClick={() => deleteObligation(o.id)} className="text-red-600 hover:underline dark:text-red-400">Hapus</button></>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {completeId !== null && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={() => setCompleteId(null)}>
                            <div className="w-full max-w-md rounded-lg bg-white p-6 shadow dark:bg-gray-800" onClick={(e) => e.stopPropagation()}>
                                <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Selesaikan Kewajiban</h3>
                                <form onSubmit={submitComplete}>
                                    <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pelaksanaan *</label>
                                    <input type="date" value={completeForm.data.last_completed} onChange={(e) => completeForm.setData('last_completed', e.target.value)} className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm" />
                                    <p className="mt-1 text-xs text-gray-500">Next due akan dihitung otomatis dari frekuensi.</p>
                                    <div className="mt-4 flex justify-end gap-2">
                                        <button type="button" onClick={() => setCompleteId(null)} className="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Batal</button>
                                        <button type="submit" disabled={completeForm.processing} className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">Tandai Selesai</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* Comments & Activities */}
                    <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ActivityCard title="Komentar" items={(register.comments ?? []).map((c) => ({ id: c.id, primary: c.content, meta: `${c.author?.name ?? '—'} · ${c.created_at}${c.internal ? ' · internal' : ''}` }))} />
                        <ActivityCard title="Aktivitas" items={(register.activities ?? []).map((a) => ({ id: a.id, primary: a.description, meta: `${a.actor?.name ?? '—'} · ${a.created_at}` }))} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-gray-500 dark:text-gray-400">{label}</dt>
            <dd className="text-gray-900 dark:text-gray-100">{value}</dd>
        </div>
    );
}

function ActivityCard({ title, items }: { title: string; items: { id: number; primary: string; meta: string }[] }) {
    return (
        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{title}</h3>
            {items.length === 0 ? (
                <p className="text-sm text-gray-500">Tidak ada data.</p>
            ) : (
                <ul className="space-y-3">
                    {items.map((it) => (
                        <li key={it.id} className="border-l-2 border-gray-200 pl-3 dark:border-gray-700">
                            <p className="text-sm text-gray-900 dark:text-gray-100">{it.primary}</p>
                            <p className="text-xs text-gray-500">{it.meta}</p>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
