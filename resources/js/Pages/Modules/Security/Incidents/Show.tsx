import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { PageProps, SecurityIncident } from '@/types';
import TypeBadge from '@/Components/Security/TypeBadge';
import StatusBadge from '@/Components/Security/StatusBadge';
import SeverityBadge from '@/Components/Security/SeverityBadge';

interface ShowProps extends PageProps {
    incident: SecurityIncident;
}

function fmtDateTime(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export default function Show({ auth, incident }: ShowProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canUpdate = permissions.has('security.incidents.update');
    const canEdit = canUpdate && (incident.status === 'reported' || incident.status === 'under_investigation');

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('security.incidents.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali ke Daftar</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">Insiden Keamanan</h2>
                </div>
            }
        >
            <Head title={`Insiden Keamanan ${incident.security_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Summary Card */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">{incident.security_number}</span>
                                    <SeverityBadge severity={incident.severity} />
                                    <StatusBadge status={incident.status} />
                                    <TypeBadge type={incident.type} />
                                </div>
                                <h3 className="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">{incident.title}</h3>
                                <dl className="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">📅 Waktu Kejadian</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{fmtDateTime(incident.occurred_at)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">🏭 Site</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.site?.name ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">📍 Area</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.area?.name ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">👤 Pelapor</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.reporter?.name ?? '-'}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div className="flex flex-wrap gap-2">
<>                                {canEdit && (
                                    <Link href={route('security.incidents.edit', incident.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                        ✏ Edit
                                    </Link>
                                )}
                        <DeleteWithConfirm
                            routeName="security.incidents.destroy"
                            id={incident.id}
                            permission="security.incidents.delete"
                            itemLabel={incident.security_number}
                            redirectTo="security.incidents.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm></>
                            </div>
                        </div>
                    </div>

                    <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                        {/* Left: Description + Resolution */}
                        <div className="space-y-6 lg:col-span-2">
                            <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">DESKRIPSI INSIDEN</h3>
                                <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{incident.description || '-'}</p>
                            </div>

                            {incident.status === 'closed' && (
                                <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                    <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">RESOLUSI</h3>
                                    <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{incident.resolution || '-'}</p>
                                    {incident.resolved_at && (
                                        <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">Ditutup: {fmtDateTime(incident.resolved_at)}</p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Right: Info sidebar */}
                        <div className="space-y-6">
                            <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">INFO LOKASI</h3>
                                <dl className="space-y-2 text-sm">
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">Site</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.site?.name ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">Area</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.area?.name ?? '-'}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PELAPOR</h3>
                                <dl className="space-y-2 text-sm">
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">Nama</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.reporter?.name ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-gray-500 dark:text-gray-400">Email</dt>
                                        <dd className="text-gray-900 dark:text-gray-100">{incident.reporter?.email ?? '-'}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">SEVERITY</h3>
                                <SeverityBadge severity={incident.severity} />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
