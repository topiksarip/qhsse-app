import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, TrainingProgram, TrainingRecord } from '@/types';
import { Head, Link } from '@inertiajs/react';

type ProgramDetail = TrainingProgram & {
    training_records?: TrainingRecord[];
};

type Statistics = {
    total_records: number;
    completed: number;
    in_progress: number;
    scheduled: number;
};

const categoryLabels: Record<string, string> = {
    safety: 'Keselamatan',
    technical: 'Teknis',
    compliance: 'Kepatuhan',
    soft_skill: 'Soft Skill',
    environment: 'Lingkungan',
    security: 'Keamanan',
    quality: 'Kualitas',
    first_aid: 'P3K',
};

const statusLabels: Record<string, string> = {
    scheduled: 'Dijadwalkan',
    in_progress: 'Berjalan',
    completed: 'Selesai',
    expired: 'Kedaluwarsa',
    cancelled: 'Dibatalkan',
};

function formatDate(value?: string) {
    return value ? new Date(value).toLocaleDateString('id-ID') : '—';
}

export default function Show({ program, statistics, can }: PageProps<{
    program: ProgramDetail;
    statistics: Statistics;
    can: { update: boolean };
}>) {
    const records = program.training_records ?? [];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-indigo-600 dark:text-indigo-400">{program.code}</p>
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">{program.name}</h2>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('training.programs.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                            ← Kembali
                        </Link>
                        {can.update && (
                            <Link href={route('training.programs.edit', program.id)} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Edit Program
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={program.name} />

            <div className="py-6">
                <div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Statistik program">
                        {[
                            ['Total Record', statistics.total_records, 'text-slate-900 dark:text-white'],
                            ['Dijadwalkan', statistics.scheduled, 'text-blue-600 dark:text-blue-400'],
                            ['Berjalan', statistics.in_progress, 'text-amber-600 dark:text-amber-400'],
                            ['Selesai', statistics.completed, 'text-emerald-600 dark:text-emerald-400'],
                        ].map(([label, value, color]) => (
                            <div key={label} className="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                                <p className="text-sm text-gray-500 dark:text-gray-400">{label}</p>
                                <p className={`mt-2 text-3xl font-bold ${color}`}>{value}</p>
                            </div>
                        ))}
                    </section>

                    <section className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                {categoryLabels[program.category] ?? program.category}
                            </span>
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${program.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'}`}>
                                {program.is_active ? 'Aktif' : 'Nonaktif'}
                            </span>
                            {program.is_certification && (
                                <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">Sertifikasi</span>
                            )}
                        </div>

                        <dl className="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt className="text-sm text-gray-500 dark:text-gray-400">Durasi</dt>
                                <dd className="mt-1 font-medium text-gray-900 dark:text-gray-100">{program.duration_hours} jam</dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500 dark:text-gray-400">Masa berlaku sertifikat</dt>
                                <dd className="mt-1 font-medium text-gray-900 dark:text-gray-100">{program.is_certification ? (program.validity_months ? `${program.validity_months} bulan` : 'Tanpa batas') : 'Tidak berlaku'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm text-gray-500 dark:text-gray-400">Dibuat</dt>
                                <dd className="mt-1 font-medium text-gray-900 dark:text-gray-100">{formatDate(program.created_at)}</dd>
                            </div>
                            <div className="sm:col-span-2 lg:col-span-3">
                                <dt className="text-sm text-gray-500 dark:text-gray-400">Deskripsi</dt>
                                <dd className="mt-1 whitespace-pre-wrap text-gray-900 dark:text-gray-100">{program.description || 'Tidak ada deskripsi.'}</dd>
                            </div>
                        </dl>
                    </section>

                    <section className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">10 Record Pelatihan Terbaru</h3>
                        </div>
                        {records.length === 0 ? (
                            <p className="p-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada record untuk program ini.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            {['Nomor', 'Karyawan', 'Tanggal Mulai', 'Status', 'Hasil'].map((label) => (
                                                <th key={label} className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {records.map((record) => (
                                            <tr key={record.id}>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                                    <Link href={route('training.records.show', record.id)}>{record.training_number}</Link>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{record.employee?.name ?? '—'}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{formatDate(record.start_date)}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{statusLabels[record.status] ?? record.status}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{record.result ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
