import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { useState } from 'react';

type Item = { id: number; question: string; type: string; category: string | null; is_required: boolean };
type Result = { inspection_item_id: number; answer: string | null; remark: string | null; is_unsafe: boolean };
type Inspection = {
    id: number; inspection_number: string; status: string; overall_result: string; scheduled_at: string; executed_at: string | null; notes: string | null;
    template: { id: number; name: string; items: Item[] };
    site?: { name: string } | null; area?: { name: string } | null; inspector?: { name: string } | null;
    results: Result[];
};

const statusColors: Record<string, string> = { pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200', completed: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' };
const resultColors: Record<string, string> = { pass: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200', fail: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200', pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' };

export default function Show({ inspection, auth }: PageProps<{ inspection: Inspection }>) {
    const permissions = new Set(auth.permissions ?? []);
    const canExecute = permissions.has('inspection.checklists.execute') && inspection.status !== 'completed';
    const [answers, setAnswers] = useState<Record<number, { answer: string; remark: string; is_unsafe: boolean }>>(() => {
        const map: Record<number, { answer: string; remark: string; is_unsafe: boolean }> = {};
        inspection.results.forEach((r) => { map[r.inspection_item_id] = { answer: r.answer ?? '', remark: r.remark ?? '', is_unsafe: r.is_unsafe }; });
        return map;
    });
    const [notes, setNotes] = useState(inspection.notes ?? '');

    function updateAnswer(itemId: number, field: 'answer' | 'remark' | 'is_unsafe', value: string | boolean) {
        setAnswers((prev) => ({ ...prev, [itemId]: { ...prev[itemId], answer: prev[itemId]?.answer ?? '', remark: prev[itemId]?.remark ?? '', is_unsafe: prev[itemId]?.is_unsafe ?? false, [field]: value } }));
    }

    function save() {
        const results = Object.entries(answers).map(([itemId, val]) => ({ inspection_item_id: Number(itemId), ...val }));
        router.put(route('inspection.checklists.update', inspection.id), { results, notes }, { preserveState: true });
    }

    function start() { router.post(route('inspection.checklists.start', inspection.id)); }
    function complete() { router.post(route('inspection.checklists.complete', inspection.id)); }

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
                </div>

                <div className="flex flex-wrap gap-2">
<>                    <Link href={route('inspection.checklists.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Kembali</Link>
                        <DeleteWithConfirm
                            routeName="inspection.checklists.destroy"
                            id={inspection.id}
                            permission="inspection.checklists.delete"
                            itemLabel={inspection.inspection_number}
                            redirectTo="inspection.checklists.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
                    {canExecute && inspection.status === 'pending' && <button onClick={start} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Mulai Inspeksi</button>}
                    {canExecute && inspection.status === 'in_progress' && <button onClick={save} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Simpan Hasil</button>}
                    {canExecute && inspection.status === 'in_progress' && <button onClick={complete} className="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Selesaikan</button>}
                </div>

                {/* Checklist Items */}
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Checklist Items</h3>
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
                                    {canExecute && (
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
                                            {item.type === 'scale' && (
                                                <select value={ans.answer} onChange={(e) => updateAnswer(item.id, 'answer', e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                                                    <option value="">—</option>{[1,2,3,4,5].map((n) => <option key={n} value={String(n)}>{n}</option>)}
                                                </select>
                                            )}
                                            <input type="text" value={ans.remark} onChange={(e) => updateAnswer(item.id, 'remark', e.target.value)} placeholder="Remark..." className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 md:col-span-2" />
                                        </div>
                                    )}
                                    {!canExecute && ans.answer && <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">Jawaban: {ans.answer}{ans.remark && ` (${ans.remark})`}</p>}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {canExecute && (
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea value={notes} onChange={(e) => setNotes(e.target.value)} rows={3} className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" />
                    </div>
                )}
                {!canExecute && inspection.notes && (
                    <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Notes</h3>
                        <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{inspection.notes}</p>
                    </div>
                )}
            </div></div>
        </AuthenticatedLayout>
    );
}
