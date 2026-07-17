import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { useState } from 'react';
import type { ManagedFile } from '@/types';

type Item = { id: number; question: string; type: string; category: string | null; is_required: boolean };
type Result = { inspection_item_id: number; answer: string | null; remark: string | null; is_unsafe: boolean; photo?: string | null };
type UnitT = { id: number; identifier: string; status: string; notes: string | null; cancelled_reason: string | null; results: Result[] };
type Inspection = {
    id: number; inspection_number: string; status: string; overall_result: string; scheduled_at: string; executed_at: string | null; notes: string | null;
    template: { id: number; name: string; items: Item[] };
    site?: { name: string } | null; area?: { name: string } | null; inspector?: { name: string } | null;
};

const statusColors: Record<string, string> = { pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200', completed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' };
const resultColors: Record<string, string> = { pass: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200', fail: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200', pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' };
const unitBadge: Record<string, { label: string; cls: string }> = {
    pending: { label: '●', cls: 'text-gray-400' },
    done: { label: '✓', cls: 'text-green-600' },
    cancelled: { label: '✗', cls: 'text-red-500' },
};

type AnswerVal = { answer: string; remark: string; is_unsafe: boolean };
type UnitAnswers = Record<number, Record<number, AnswerVal>>;

export default function Show({ inspection, units, auth, files = [] }: PageProps<{ inspection: Inspection; units: UnitT[]; files?: ManagedFile[] }>) {
    const permissions = new Set(auth.permissions ?? []);
    const canExecute = permissions.has('inspection.checklists.execute') && inspection.status !== 'completed';

    const initialUnit = units.find((u) => u.status === 'pending') ?? units[0];
    const [selectedUnitId, setSelectedUnitId] = useState<number>(initialUnit?.id ?? 0);

    const buildUnitAnswers = (): UnitAnswers => {
        const map: UnitAnswers = {};
        for (const u of units) {
            map[u.id] = {};
            for (const r of u.results) {
                map[u.id][r.inspection_item_id] = { answer: r.answer ?? '', remark: r.remark ?? '', is_unsafe: r.is_unsafe };
            }
        }
        return map;
    };

    const [unitAnswers, setUnitAnswers] = useState<UnitAnswers>(buildUnitAnswers());
    const [photoFiles, setPhotoFiles] = useState<Record<number, File>>({});
    const [photoPaths, setPhotoPaths] = useState<Record<number, string | null>>(() => {
        const map: Record<number, string | null> = {};
        for (const u of units) for (const r of u.results) map[r.inspection_item_id] = r.photo ?? null;
        return map;
    });
    const [cancelReason, setCancelReason] = useState('');
    const [showCancel, setShowCancel] = useState(false);

    const selectedUnit = units.find((u) => u.id === selectedUnitId) ?? units[0];
    const answers = unitAnswers[selectedUnit?.id] ?? {};

    function updateAnswer(itemId: number, field: 'answer' | 'remark' | 'is_unsafe', value: string | boolean) {
        if (!selectedUnit) return;
        setUnitAnswers((prev) => {
            const forUnit = prev[selectedUnit.id] ?? {};
            const cur = forUnit[itemId] ?? { answer: '', remark: '', is_unsafe: false };
            return { ...prev, [selectedUnit.id]: { ...forUnit, [itemId]: { ...cur, [field]: value } } };
        });
    }

    function saveUnit() {
        if (!selectedUnit) return;
        const fd = new FormData();
        fd.append('_method', 'put');
        const answersForUnit = unitAnswers[selectedUnit.id] ?? {};
        Object.entries(answersForUnit).forEach(([itemId, val]) => {
            fd.append(`results[${itemId}][inspection_item_id]`, itemId);
            fd.append(`results[${itemId}][answer]`, val.answer ?? '');
            fd.append(`results[${itemId}][remark]`, val.remark ?? '');
            fd.append(`results[${itemId}][is_unsafe]`, val.is_unsafe ? '1' : '0');
            const ph = photoFiles[Number(itemId)];
            if (ph) fd.append(`results[${itemId}][photo]`, ph);
        });
        router.post(route('inspection.checklists.units.update', { inspection: inspection.id, unit: selectedUnit.id }), fd, { preserveState: true });
    }

    function confirmCancel() {
        if (!selectedUnit || !cancelReason.trim()) return;
        router.post(route('inspection.checklists.units.cancel', { inspection: inspection.id, unit: selectedUnit.id }), { cancelled_reason: cancelReason }, {
            onSuccess: () => { setShowCancel(false); setCancelReason(''); },
        });
    }

    function start() { router.post(route('inspection.checklists.start', inspection.id)); }
    function complete() { router.post(route('inspection.checklists.complete', inspection.id)); }

    const hasPending = units.some((u) => u.status === 'pending');

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{inspection.inspection_number}</h2>}>
            <Head title={`Inspeksi ${inspection.inspection_number}`} />
            <div className="py-12"><div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="text-2xl font-bold text-gray-900 dark:text-gray-100">{inspection.inspection_number}</span>
                        <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${statusColors[inspection.status] ?? ''}`}>{inspection.status}</span>
                        {inspection.status === 'completed' && <span className={`inline-flex rounded-full px-3 py-1 text-sm font-semibold ${resultColors[inspection.overall_result] ?? ''}`}>Result: {inspection.overall_result}</span>}
                    </div>
                    <h1 className="mt-3 text-xl font-semibold text-gray-900 dark:text-gray-100">{inspection.template.name}</h1>
                    <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span>Site: {inspection.site?.name ?? '-'}</span>
                        {inspection.area && <span>Area: {inspection.area.name}</span>}
                        <span>Inspector: {inspection.inspector?.name ?? '-'}</span>
                        <span>Jadwal: {new Date(inspection.scheduled_at).toLocaleDateString('id-ID')}</span>
                    </div>
                    <div className="mt-2 text-sm text-gray-500">Unit: {units.filter((u) => u.status === 'done').length}/{units.length} selesai</div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Link href={route('inspection.checklists.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Kembali</Link>
                    <DeleteWithConfirm
                        routeName="inspection.checklists.destroy"
                        id={inspection.id}
                        permission="inspection.checklists.delete"
                        itemLabel={inspection.inspection_number}
                        redirectTo="inspection.checklists.index"
                        className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                    >
                        Hapus
                    </DeleteWithConfirm>
                    {canExecute && inspection.status === 'pending' && <button onClick={start} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Mulai Inspeksi</button>}
                    {canExecute && inspection.status === 'in_progress' && selectedUnit?.status !== 'cancelled' && <button onClick={saveUnit} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Simpan Hasil</button>}
                    {canExecute && inspection.status === 'in_progress' && selectedUnit?.status !== 'cancelled' && <button onClick={() => setShowCancel(true)} className="rounded-md bg-red-100 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300">Cancel Inspeksi</button>}
                    {canExecute && inspection.status === 'in_progress' && (
                        <button onClick={complete} disabled={hasPending} title={hasPending ? 'Masih ada unit pending (cancel atau simpan dulu)' : ''} className={`rounded-md px-4 py-2 text-sm font-medium text-white ${hasPending ? 'cursor-not-allowed bg-gray-300 dark:bg-gray-600' : 'bg-green-600 hover:bg-green-700'}`}>Selesaikan</button>
                    )}
                    <Link href={route('inspection.checklists.units.export', inspection.id)} className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Export Unit</Link>
                </div>

                {showCancel && (
                    <div className="rounded-lg border border-red-200 bg-white p-4 shadow-sm dark:bg-gray-800">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Cancel Inspeksi Unit {selectedUnit?.identifier}</label>
                        <textarea value={cancelReason} onChange={(e) => setCancelReason(e.target.value)} rows={2} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                        <div className="mt-2 flex gap-2">
                            <button onClick={confirmCancel} disabled={!cancelReason.trim()} className="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white disabled:opacity-50">Konfirmasi Cancel</button>
                            <button onClick={() => setShowCancel(false)} className="rounded-md bg-gray-200 px-3 py-1.5 text-sm text-gray-700">Tutup</button>
                        </div>
                    </div>
                )}

                {/* Unit selector */}
                <div className="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Unit</label>
                    <select value={selectedUnitId} onChange={(e) => setSelectedUnitId(Number(e.target.value))} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                        {units.map((u) => {
                            const b = unitBadge[u.status] ?? unitBadge.pending;
                            return <option key={u.id} value={u.id}>{`${b.label} ${u.identifier} (${u.status})`}</option>;
                        })}
                    </select>
                    {selectedUnit?.status === 'cancelled' && <p className="mt-2 text-sm text-red-600">Dibatalkan: {selectedUnit.cancelled_reason}</p>}
                </div>

                {/* Checklist Items for selected unit */}
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Checklist — {selectedUnit?.identifier}</h3>
                    <div className="space-y-4">
                        {inspection.template.items.map((item) => {
                            const ans = answers[item.id] ?? { answer: '', remark: '', is_unsafe: false };
                            return (
                                <div key={item.id} className={`rounded-md border p-4 ${ans.is_unsafe ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700'}`}>
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{item.question}</p>
                                            <p className="text-xs text-gray-500">Type: {item.type} {item.is_required && '• Required'}</p>
                                        </div>
                                        {ans.is_unsafe && <span className="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800">UNSAFE</span>}
                                    </div>
                                    {canExecute && selectedUnit?.status !== 'cancelled' && (
                                        <div className="mt-3 grid gap-2 md:grid-cols-3">
                                            {item.type === 'yes_no' && (
                                                <select value={ans.answer} onChange={(e) => updateAnswer(item.id, 'answer', e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                    <option value="">—</option><option value="yes">Yes</option><option value="no">No</option>
                                                </select>
                                            )}
                                            {item.type === 'safe_unsafe' && (
                                                <select value={ans.answer} onChange={(e) => { updateAnswer(item.id, 'answer', e.target.value); updateAnswer(item.id, 'is_unsafe', e.target.value === 'unsafe'); }} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                    <option value="">—</option><option value="safe">Safe</option><option value="unsafe">Unsafe</option>
                                                </select>
                                            )}
                                            {item.type === 'na' && (
                                                <select value={ans.answer} onChange={(e) => updateAnswer(item.id, 'answer', e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                    <option value="">—</option><option value="na">N/A</option><option value="ok">OK</option>
                                                </select>
                                            )}
                                            {item.type === 'text' && <input type="text" value={ans.answer} onChange={(e) => updateAnswer(item.id, 'answer', e.target.value)} placeholder="Jawaban..." className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />}
                                            {item.type === 'yes_no_na' && (
                                                <select value={ans.answer} onChange={(e) => updateAnswer(item.id, 'answer', e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                    <option value="">—</option><option value="yes">Yes</option><option value="no">No</option><option value="na">N/A</option>
                                                </select>
                                            )}
                                            {item.type === 'photo' && (
                                                <div className="md:col-span-3">
                                                    <input
                                                        type="file"
                                                        accept="image/*"
                                                        onChange={(e) => { const f = e.target.files?.[0]; if (f) { setPhotoFiles((prev) => ({ ...prev, [item.id]: f })); } }}
                                                        className="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-400"
                                                    />
                                                    {photoPaths[item.id]
                                                        ? (() => { const f = files.find((x) => x.file_path === photoPaths[item.id]); return f?.url ? <img src={f.url} alt="evidence" className="mt-2 h-32 w-auto rounded object-cover" /> : null; })()
                                                        : null}
                                                </div>
                                            )}
                                            <input type="text" value={ans.remark} onChange={(e) => updateAnswer(item.id, 'remark', e.target.value)} placeholder="Remark..." className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 md:col-span-2" />
                                        </div>
                                    )}
                                    {(!canExecute || selectedUnit?.status === 'cancelled') && ans.answer && <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">Jawaban: {ans.answer}{ans.remark && ` (${ans.remark})`}</p>}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {canExecute && selectedUnit?.status !== 'cancelled' && (
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes Unit</label>
                        <textarea value={selectedUnit?.notes ?? ''} readOnly className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-100 dark:text-gray-400" rows={3} />
                    </div>
                )}
            </div></div>
        </AuthenticatedLayout>
    );
}
