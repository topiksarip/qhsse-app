import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { EmergencyDrill, EmergencyPlan, Site, User, ActivityLog, PageProps } from '@/types';

interface DrillShowProps extends PageProps {
    drill: EmergencyDrill & {
        emergency_plan: EmergencyPlan;
        site: Site;
        observer: User;
    };
    activities: ActivityLog[];
    can: {
        update: boolean;
        execute: boolean;
        export: boolean;
    };
}

const drillStatusColors: Record<string, string> = {
    scheduled: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    executed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
};

const drillStatusLabels: Record<string, string> = {
    scheduled: 'Terjadwal',
    executed: 'Selesai',
};

const drillResultColors: Record<string, string> = {
    pass: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    fail: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    needs_improvement: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
};

const drillResultLabels: Record<string, string> = {
    pass: 'Lulus',
    fail: 'Gagal',
    needs_improvement: 'Perlu Perbaikan',
};

export default function Show({ auth, drill, activities, can }: DrillShowProps) {
    const { data, setData, post, processing, errors } = useForm({
        executed_date: '',
        participants_count: '',
        result: '',
        findings: '',
        recommendations: '',
    });

    const submitExecution: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('emergency.drills.execute.store', drill.id));
    };

    const handleExport = () => {
        window.location.href = route('emergency.drills.export', drill.id);
    };

    const getStatusBadge = (drillStatus: string) => {
        const colorClass = drillStatusColors[drillStatus] || drillStatusColors.scheduled;
        const label = drillStatusLabels[drillStatus] || drillStatus;
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>{label}</span>;
    };

    const getResultBadge = (drillResult: 'pass' | 'fail' | 'needs_improvement' | null) => {
        if (!drillResult) return <span className="text-gray-400 dark:text-gray-500">— (belum ada)</span>;
        const colorClass = drillResultColors[drillResult] || 'bg-gray-100 text-gray-800';
        const label = drillResultLabels[drillResult] || drillResult;
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>{label}</span>;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('emergency.drills.index')}
                            className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                        >
                            ← Kembali
                        </Link>
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {drill.drill_number} — Latihan Darurat
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                📋 Detail Latihan
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {can.update && drill.status === 'scheduled' && (
                            <Link
                                href={route('emergency.drills.edit', drill.id)}
                                className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                ✏ Edit
                            </Link>
                        )}
                        {can.export && (
                            <button
                                onClick={handleExport}
                                className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                ⬇ Export
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${drill.drill_number} — Latihan Darurat`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Informasi Latihan */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                INFORMASI LATIHAN
                            </h3>
                            <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{drill.drill_number}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Rencana Darurat</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {drill.emergency_plan?.plan_number} — {drill.emergency_plan?.name}
                                        <Link
                                            href={route('emergency.plans.show', drill.emergency_plan?.id)}
                                            className="ml-2 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            👁 Lihat Rencana
                                        </Link>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Site</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{drill.site?.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Terjadwal</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{drill.scheduled_date}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Eksekusi</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {drill.executed_date || '— (belum dieksekusi)'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Observer</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{drill.observer?.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd className="mt-1 text-sm">{getStatusBadge(drill.status)}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Hasil</dt>
                                    <dd className="mt-1 text-sm">{getResultBadge(drill.result ?? null)}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Hasil Eksekusi */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                HASIL EKSEKUSI
                            </h3>

                            {drill.status === 'scheduled' && can.execute ? (
                                <div className="border-2 border-blue-300 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700 rounded-lg p-6">
                                    <h4 className="text-lg font-bold text-blue-800 dark:text-blue-200 mb-4">
                                        Form Eksekusi Latihan
                                    </h4>
                                    <form onSubmit={submitExecution} className="space-y-4">
                                        <div>
                                            <InputLabel htmlFor="executed_date" value="Tanggal Eksekusi *" />
                                            <TextInput
                                                id="executed_date"
                                                type="date"
                                                name="executed_date"
                                                value={data.executed_date}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('executed_date', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.executed_date} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="participants_count" value="Jumlah Peserta *" />
                                            <TextInput
                                                id="participants_count"
                                                type="number"
                                                name="participants_count"
                                                min="0"
                                                value={data.participants_count}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('participants_count', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.participants_count} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="result" value="Hasil *" />
                                            <select
                                                id="result"
                                                name="result"
                                                value={data.result}
                                                onChange={(e) => setData('result', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                required
                                            >
                                                <option value="">— Pilih Hasil —</option>
                                                <option value="pass">Lulus</option>
                                                <option value="fail">Gagal</option>
                                                <option value="needs_improvement">Perlu Perbaikan</option>
                                            </select>
                                            <InputError message={errors.result} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="findings" value="Temuan" />
                                            <textarea
                                                id="findings"
                                                name="findings"
                                                value={data.findings}
                                                onChange={(e) => setData('findings', e.target.value)}
                                                rows={4}
                                                className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                placeholder="Temuan dari latihan..."
                                            />
                                            <InputError message={errors.findings} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="recommendations" value="Rekomendasi" />
                                            <textarea
                                                id="recommendations"
                                                name="recommendations"
                                                value={data.recommendations}
                                                onChange={(e) => setData('recommendations', e.target.value)}
                                                rows={4}
                                                className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                placeholder="Rekomendasi perbaikan..."
                                            />
                                            <InputError message={errors.recommendations} className="mt-2" />
                                        </div>

                                        <div className="flex justify-end">
                                            <PrimaryButton disabled={processing} className="bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:ring-blue-500">
                                                {processing ? 'Menyimpan...' : 'Eksekusi'}
                                            </PrimaryButton>
                                        </div>
                                    </form>
                                </div>
                            ) : drill.status === 'executed' ? (
                                <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Eksekusi</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{drill.executed_date}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Jumlah Peserta</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{drill.participants_count}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Hasil</dt>
                                        <dd className="mt-1 text-sm">{getResultBadge(drill.result ?? null)}</dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Temuan</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                            {drill.findings || '—'}
                                        </dd>
                                    </div>
                                    <div className="sm:col-span-2">
                                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Rekomendasi</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                            {drill.recommendations || '—'}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <p className="text-gray-500 dark:text-gray-400 text-sm">
                                    Belum ada data eksekusi untuk latihan ini.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Aktivitas */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                AKTIVITAS
                            </h3>
                            {activities.length > 0 ? (
                                <div className="space-y-3">
                                    {activities.map((activity) => (
                                        <div key={activity.id} className="flex items-start gap-3">
                                            <div className="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-gray-400 dark:bg-gray-500"></div>
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-900 dark:text-gray-100">
                                                    <span className="text-gray-500 dark:text-gray-400">{activity.created_at}</span> — {activity.description}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 dark:text-gray-400 text-sm">
                                    Belum ada aktivitas tercatat.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
