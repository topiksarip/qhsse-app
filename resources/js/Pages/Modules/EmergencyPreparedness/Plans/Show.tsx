import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyPlan, EmergencyDrill, Site, User, ManagedFile, Comment, ActivityLog, PageProps } from '@/types';

interface PlanShowProps extends PageProps {
    plan: EmergencyPlan & {
        site: Site;
        contact_person: User;
        emergency_contacts: User[];
        drills: EmergencyDrill[];
    };
    evidence: ManagedFile[];
    comments: Comment[];
    activities: ActivityLog[];
    can: {
        update: boolean;
        export: boolean;
        createDrill: boolean;
    };
}

const planTypeColors: Record<string, string> = {
    fire: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    medical: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    spill: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    evacuation: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    natural_disaster: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    security: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    other: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};

const planTypeLabels: Record<string, string> = {
    fire: 'Kebakaran',
    medical: 'Medis',
    spill: 'Tumpahan',
    evacuation: 'Evakuasi',
    natural_disaster: 'Bencana Alam',
    security: 'Keamanan',
    other: 'Lainnya',
};

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

export default function Show({ auth, plan, evidence, comments, activities, can }: PlanShowProps) {
    const { data, setData, post, processing } = useForm({
        comment: '',
    });

    const submitComment: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('emergency.plans.comments.store', plan.id), {
            onSuccess: () => setData('comment', ''),
        });
    };

    const handleExport = () => {
        window.location.href = route('emergency.plans.export', plan.id);
    };

    const getTypeBadge = (planType: string) => {
        const colorClass = planTypeColors[planType] || planTypeColors.other;
        const label = planTypeLabels[planType] || planType;
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass}`}>{label}</span>;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('emergency.plans.index')}
                            className="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                        >
                            ← Kembali
                        </Link>
                        <div>
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {plan.plan_number} — {plan.name}
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                📋 Rencana Darurat
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {can.update && (
                            <Link
                                href={route('emergency.plans.edit', plan.id)}
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
            <Head title={`${plan.plan_number} — ${plan.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Informasi Umum */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                INFORMASI UMUM
                            </h3>
                            <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{plan.plan_number}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nama</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{plan.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tipe</dt>
                                    <dd className="mt-1 text-sm">{getTypeBadge(plan.type)}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Site</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{plan.site?.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Kontak Person</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{plan.contact_person?.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Dibuat Pada</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{plan.created_at}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Deskripsi */}
                    {plan.description && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    DESKRIPSI
                                </h3>
                                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{plan.description}</p>
                            </div>
                        </div>
                    )}

                    {/* Prosedur Respons */}
                    {plan.response_procedure && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    PROSEDUR RESPONS
                                </h3>
                                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{plan.response_procedure}</p>
                            </div>
                        </div>
                    )}

                    {/* Prosedur Eskalasi */}
                    {plan.escalation_procedure && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    PROSEDUR ESKALASI
                                </h3>
                                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{plan.escalation_procedure}</p>
                            </div>
                        </div>
                    )}

                    {/* Kontak Darurat Tambahan */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                KONTAK DARURAT TAMBAHAN
                            </h3>
                            {plan.emergency_contacts && plan.emergency_contacts.length > 0 ? (
                                <div className="space-y-2">
                                    {plan.emergency_contacts.map((contact: any) => (
                                        <div key={contact.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                            <div className="flex justify-between">
                                                <span className="font-medium text-sm text-gray-900 dark:text-gray-100">{contact.name}</span>
                                                <span className="text-sm text-gray-500 dark:text-gray-400">{contact.phone}</span>
                                            </div>
                                            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">{contact.role}</div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Tidak ada kontak tambahan.</p>
                            )}
                        </div>
                    </div>

                    {/* Peralatan */}
                    {plan.equipment_needed && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    PERALATAN YANG DIBUTUHKAN
                                </h3>
                                <p className="text-sm text-gray-700 dark:text-gray-300">{plan.equipment_needed}</p>
                            </div>
                        </div>
                    )}

                    {/* Latihan Darurat Terkait */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    LATIHAN DARURAT TERKAIT
                                </h3>
                                {can.createDrill && (
                                    <Link
                                        href={route('emergency.drills.create', { plan_id: plan.id })}
                                        className="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        + Jadwalkan Latihan
                                    </Link>
                                )}
                            </div>
                            {plan.drills && plan.drills.length > 0 ? (
                                <div className="space-y-2">
                                    {plan.drills.map((drill) => (
                                        <div key={drill.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${drillStatusColors[drill.status]}`}>
                                                    {drillStatusLabels[drill.status]}
                                                </span>
                                                {drill.result && (
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${drillResultColors[drill.result]}`}>
                                                        {drillResultLabels[drill.result]}
                                                    </span>
                                                )}
                                                <span className="font-mono text-sm text-gray-900 dark:text-gray-100">{drill.drill_number}</span>
                                                <span className="text-sm text-gray-500 dark:text-gray-400">{drill.scheduled_date}</span>
                                                {drill.participants_count && (
                                                    <span className="text-sm text-gray-500 dark:text-gray-400">{drill.participants_count} peserta</span>
                                                )}
                                            </div>
                                            <Link
                                                href={route('emergency.drills.show', drill.id)}
                                                className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                👁 Lihat
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada latihan terjadwal.</p>
                            )}
                        </div>
                    </div>

                    {/* Evidence */}
                    {evidence && evidence.length > 0 && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    EVIDENCE
                                </h3>
                                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    {evidence.map((file) => (
                                        <div key={file.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                                            <div className="text-4xl mb-2">📄</div>
                                            <div className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{file.file_name}</div>
                                            <div className="text-xs text-gray-500 dark:text-gray-400 mb-2">{file.file_size}</div>
                                            <a
                                                href={route('files.download', file.id)}
                                                className="text-xs text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                            >
                                                ⬇ Download
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Aktivitas & Komentar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                AKTIVITAS & KOMENTAR
                            </h3>
                            
                            {/* Timeline */}
                            {activities && activities.length > 0 && (
                                <div className="mb-6 space-y-2">
                                    {activities.map((activity) => (
                                        <div key={activity.id} className="text-sm text-gray-700 dark:text-gray-300">
                                            ◉ {activity.created_at} — {activity.description}
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Comments */}
                            {comments && comments.length > 0 && (
                                <div className="mb-4 space-y-3">
                                    {comments.map((comment) => (
                                        <div key={comment.id} className="border-l-4 border-gray-300 dark:border-gray-600 pl-4">
                                            <div className="flex justify-between">
                                                <span className="font-medium text-sm text-gray-900 dark:text-gray-100">{comment.user?.name}</span>
                                                <span className="text-xs text-gray-500 dark:text-gray-400">{comment.created_at}</span>
                                            </div>
                                            <p className="text-sm text-gray-700 dark:text-gray-300 mt-1">{comment.comment_text}</p>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Add Comment Form */}
                            <form onSubmit={submitComment} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <textarea
                                    value={data.comment}
                                    onChange={(e) => setData('comment', e.target.value)}
                                    placeholder="Tulis komentar..."
                                    rows={3}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                />
                                <div className="mt-2 flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={processing || !data.comment}
                                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        Kirim
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
