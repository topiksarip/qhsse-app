import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, EnvironmentalRecord } from '@/types';
import TypeBadge from '@/Components/Environmental/TypeBadge';
import StatusBadge from '@/Components/Environmental/StatusBadge';
import ExceedanceBadge from '@/Components/Environmental/ExceedanceBadge';
import ExceedanceAlert from '@/Components/Environmental/ExceedanceAlert';

interface ShowProps extends PageProps {
    record: EnvironmentalRecord;
}

function fmt(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function fmtDate(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function Show({ auth, record }: ShowProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canUpdate = permissions.has('environment.records.update');
    const canEdit = canUpdate && (record.status === 'recorded' || record.status === 'investigated');
    const measured = record.measured_value != null ? `${record.measured_value} ${record.unit ?? ''}` : '-';
    const limit = record.limit_value != null ? `${record.limit_value} ${record.unit ?? ''}` : '-';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link href={route('environment.records.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali</Link>
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{record.record_number}</h2>
                            <TypeBadge type={record.type} />
                            <StatusBadge status={record.status} />
                            <ExceedanceBadge isExceedance={record.is_exceedance} />
                        </div>
                        <p className="mt-1 font-medium text-gray-900 dark:text-gray-100">{record.title}</p>
                    </div>
                    <div className="flex gap-2">
                        {canEdit && (
                            <Link href={route('environment.records.edit', record.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">✏ Edit</Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Catatan Lingkungan ${record.record_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {record.is_exceedance && <ExceedanceAlert record={record} />}

                    {/* Informasi Umum */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">INFORMASI UMUM</h3>
                        <dl className="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2 dark:border-gray-700">
                            <div><dt className="text-xs text-gray-500">Nomor</dt><dd className="font-mono text-sm text-gray-900 dark:text-gray-100">{record.record_number}</dd></div>
                            <div><dt className="text-xs text-gray-500">Tipe</dt><dd className="text-sm"><TypeBadge type={record.type} /></dd></div>
                            <div className="md:col-span-2"><dt className="text-xs text-gray-500">Judul</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.title}</dd></div>
                            <div><dt className="text-xs text-gray-500">Status</dt><dd className="text-sm"><StatusBadge status={record.status} /></dd></div>
                            <div><dt className="text-xs text-gray-500">Exceedance</dt><dd className="text-sm"><ExceedanceBadge isExceedance={record.is_exceedance} /> {!record.is_exceedance && <span className="text-sm text-gray-500">Normal</span>}</dd></div>
                            <div><dt className="text-xs text-gray-500">Tanggal Kejadian</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{fmt(record.occurred_at)}</dd></div>
                            <div><dt className="text-xs text-gray-500">Dibuat Oleh</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.reporter?.name ?? '-'}</dd></div>
                            <div className="md:col-span-2"><dt className="text-xs text-gray-500">Dibuat Pada</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{fmt(record.created_at)}</dd></div>
                        </dl>
                    </div>

                    {/* Pengukuran */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">PENGUKURAN</h3>
                        <dl className="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2 dark:border-gray-700">
                            {record.parameter && <div><dt className="text-xs text-gray-500">Parameter</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.parameter}</dd></div>}
                            <div><dt className="text-xs text-gray-500">Nilai Terukur</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{measured}</dd></div>
                            <div><dt className="text-xs text-gray-500">Batas</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{limit}</dd></div>
                            {record.waste_type && <div><dt className="text-xs text-gray-500">Jenis Limbah</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.waste_type}</dd></div>}
                            {record.quantity != null && <div><dt className="text-xs text-gray-500">Jumlah</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.quantity} {record.unit}</dd></div>}
                            {record.disposal_method && <div><dt className="text-xs text-gray-500">Metode Pembuangan</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.disposal_method}</dd></div>}
                            {record.material && <div><dt className="text-xs text-gray-500">Material</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.material}</dd></div>}
                            {record.volume != null && <div><dt className="text-xs text-gray-500">Volume</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.volume} {record.unit}</dd></div>}
                            {record.containment && <div><dt className="text-xs text-gray-500">Penahanan</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.containment}</dd></div>}
                            {record.location && <div><dt className="text-xs text-gray-500">Lokasi</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.location}</dd></div>}
                        </dl>
                    </div>

                    {/* Lokasi */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">LOKASI</h3>
                        <dl className="grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 md:grid-cols-2 dark:border-gray-700">
                            <div><dt className="text-xs text-gray-500">Site</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.site?.name ?? '-'}</dd></div>
                            <div><dt className="text-xs text-gray-500">Area</dt><dd className="text-sm text-gray-900 dark:text-gray-100">{record.area?.name ?? '-'}</dd></div>
                        </dl>
                    </div>

                    {/* Deskripsi */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">DESKRIPSI</h3>
                        <p className="whitespace-pre-wrap border-t border-gray-200 pt-4 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">{record.description || '-'}</p>
                    </div>

                    {/* CAPA Terkait */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 className="mb-3 text-lg font-medium text-gray-900 dark:text-gray-100">CAPA TERKAIT</h3>
                        <div className="border-t border-gray-200 pt-4 dark:border-gray-700">
                            {record.capa_action ? (
                                <div className="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <div>
                                        <span className="font-mono text-sm">{record.capa_action.number}</span>
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{record.capa_action.title}</p>
                                    </div>
                                    <Link href={route('capa.actions.show', record.capa_action.id)} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                                        👁 Lihat CAPA →
                                    </Link>
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada CAPA terkait.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
