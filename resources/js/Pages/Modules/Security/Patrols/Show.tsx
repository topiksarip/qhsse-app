import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import CheckpointResultCard from './CheckpointResultCard';
import { Option, Patrol, statusClasses, statusLabels } from './types';

interface Activity {
    id: number;
    description: string;
    actor_name: string | null;
    created_at: string;
}

interface Props extends PageProps {
    patrol: Patrol;
    activities: Activity[];
    resultOptions: Record<string, string>;
    can: { update: boolean; execute: boolean };
}

export default function Show({ patrol, activities, resultOptions, can }: Props) {
    const page = usePage<PageProps & { errors: Record<string, string> }>();
    const officer = typeof patrol.assigned_to === 'object' ? patrol.assigned_to : null;
    const completedBy = typeof patrol.completed_by === 'object' ? patrol.completed_by : null;
    const pending = patrol.results.filter((result) => !result.result).length;
    const issues = patrol.results.filter((result) => result.result === 'issue').length;

    const start = () => {
        if (confirm('Mulai eksekusi patroli sekarang?')) {
            router.post(route('security.patrols.start', patrol.id), {}, { preserveScroll: true });
        }
    };
    const complete = () => {
        if (confirm('Selesaikan patroli? Hasil tidak dapat diubah setelah selesai.')) {
            router.post(route('security.patrols.complete', patrol.id), {}, { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Patroli ${patrol.patrol_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <Link href={route('security.patrols.index')} className="text-sm text-blue-600">← Daftar Patroli</Link>
                            <div className="mt-2 flex flex-wrap items-center gap-3">
                                <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">{patrol.patrol_number}</h1>
                                <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses[patrol.status]}`}>{statusLabels[patrol.status]}</span>
                            </div>
                            <p className="mt-1 text-slate-600 dark:text-slate-300">{patrol.title}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {can.update && <Link href={route('security.patrols.edit', patrol.id)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium dark:border-slate-600 dark:text-white">Edit</Link>}
                            {can.execute && patrol.status === 'scheduled' && <button onClick={start} className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white">▶ Mulai Eksekusi</button>}
                            {can.execute && patrol.status === 'in_progress' && <button onClick={complete} disabled={pending > 0} className="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50">✓ Selesaikan</button>}
                        </div>
                    </header>

                    <InputError message={page.props.errors.status || page.props.errors.results} />

                    <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            <Info label="Site" value={patrol.site.name} />
                            <Info label="Area" value={patrol.area?.name ?? 'Semua area'} />
                            <Info label="Petugas" value={officer?.name ?? '—'} />
                            <Info label="Jadwal" value={formatDate(patrol.scheduled_at)} />
                            <Info label="Mulai" value={formatDate(patrol.started_at)} />
                            <Info label="Selesai" value={formatDate(patrol.completed_at)} />
                            <Info label="Diselesaikan Oleh" value={completedBy?.name ?? '—'} />
                            <Info label="Progress" value={`${patrol.results.length - pending}/${patrol.results.length} checkpoint`} />
                        </div>
                        {patrol.description && <div className="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700"><h2 className="text-sm font-medium text-slate-500">Deskripsi</h2><p className="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{patrol.description}</p></div>}
                    </section>

                    <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                        <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                            <div><h2 className="text-lg font-semibold text-slate-900 dark:text-white">Hasil Checkpoint</h2><p className="text-sm text-slate-500">{pending} belum diperiksa · {issues} issue</p></div>
                            {patrol.status === 'scheduled' && <span className="text-sm text-amber-600">Mulai eksekusi untuk mengisi hasil.</span>}
                        </div>
                        <div className="space-y-4">
                            {patrol.results.map((result) => <CheckpointResultCard key={result.id} patrolId={patrol.id} result={result} resultOptions={resultOptions} editable={can.execute && patrol.status === 'in_progress'} />)}
                        </div>
                    </section>

                    {patrol.notes && <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800"><h2 className="font-semibold text-slate-900 dark:text-white">Catatan Patroli</h2><p className="mt-2 whitespace-pre-wrap text-sm text-slate-600 dark:text-slate-300">{patrol.notes}</p></section>}

                    <section className="rounded-xl bg-white p-6 shadow dark:bg-slate-800">
                        <h2 className="mb-4 font-semibold text-slate-900 dark:text-white">Audit Aktivitas</h2>
                        <div className="space-y-4">
                            {activities.map((activity) => <div key={activity.id} className="border-l-2 border-blue-500 pl-4"><p className="text-sm text-slate-700 dark:text-slate-200">{activity.description}</p><p className="text-xs text-slate-500">{activity.actor_name ?? 'Sistem'} · {formatDate(activity.created_at)}</p></div>)}
                            {activities.length === 0 && <p className="text-sm text-slate-500">Belum ada aktivitas.</p>}
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Info({ label, value }: { label: string; value: string }) {
    return <div><dt className="text-xs font-medium uppercase text-slate-500">{label}</dt><dd className="mt-1 text-sm font-medium text-slate-900 dark:text-white">{value}</dd></div>;
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}
