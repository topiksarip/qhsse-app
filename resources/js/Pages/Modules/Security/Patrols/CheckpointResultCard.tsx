import InputError from '@/Components/InputError';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Patrol, PatrolResult } from './types';

interface Props {
    patrolId: number;
    result: PatrolResult;
    editable: boolean;
    resultOptions: Record<string, string>;
}

export default function CheckpointResultCard({ patrolId, result, editable, resultOptions }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        result: result.result ?? '',
        findings: result.findings ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('security.patrols.results.store', [patrolId, result.id]), {
            preserveScroll: true,
        });
    };

    const badgeClass: Record<Exclude<PatrolResult['result'], null>, string> = {
        ok: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
        issue: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
        na: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    };

    return (
        <form onSubmit={submit} className={`rounded-xl border p-5 ${result.result === 'issue' ? 'border-red-300 bg-red-50/40 dark:border-red-800 dark:bg-red-950/20' : 'border-slate-200 dark:border-slate-700'}`}>
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-slate-900 dark:text-white">{result.checkpoint}</h3>
                    {result.checked_at && <p className="mt-1 text-xs text-slate-500">Diperiksa {new Date(result.checked_at).toLocaleString('id-ID')}</p>}
                </div>
                {result.result && <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${badgeClass[result.result]}`}>{resultOptions[result.result]}</span>}
            </div>

            {editable ? (
                <>
                    <div className="mt-4 grid gap-2 sm:grid-cols-3">
                        {Object.entries(resultOptions).map(([value, label]) => (
                            <label key={value} className={`cursor-pointer rounded-lg border p-3 text-center text-sm ${data.result === value ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-950' : 'border-slate-200 dark:border-slate-700 dark:text-slate-200'}`}>
                                <input type="radio" name={`result-${result.id}`} value={value} checked={data.result === value} onChange={() => setData('result', value as PatrolResult['result'] & string)} className="mr-2" />
                                {label}
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.result} className="mt-2" />
                    <label className="mt-4 block text-sm font-medium text-slate-700 dark:text-slate-200">
                        Catatan / Temuan {data.result === 'issue' && <span className="text-red-600">*</span>}
                    </label>
                    <textarea value={data.findings} onChange={(e) => setData('findings', e.target.value)} rows={3} placeholder={data.result === 'issue' ? 'Jelaskan issue dan kondisi yang ditemukan...' : 'Catatan opsional'} className="mt-1 block w-full rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                    <InputError message={errors.findings} className="mt-2" />
                    <div className="mt-4 flex items-center justify-end gap-3">
                        {recentlySuccessful && <span className="text-sm text-green-600">Tersimpan</span>}
                        <button disabled={processing || !data.result} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Simpan Hasil</button>
                    </div>
                </>
            ) : (
                <div className="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                    {result.findings || (result.result ? 'Tidak ada catatan.' : 'Checkpoint belum diperiksa.')}
                </div>
            )}
        </form>
    );
}
