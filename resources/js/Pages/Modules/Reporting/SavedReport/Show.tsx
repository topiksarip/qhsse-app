import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ReportTemplate } from '@/types/modules/reporting';
import { Head, Link, router } from '@inertiajs/react';

type UserSummary = { id: number; name: string; email: string };
type ReportParameters = {
    date_from?: string;
    date_to?: string;
    site_id?: number | null;
    department_id?: number | null;
    include_charts?: boolean;
    [key: string]: unknown;
};

type ReportDetail = {
    id: number;
    name: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    format: 'csv' | 'pdf' | 'excel';
    parameters: ReportParameters;
    file_size: number | null;
    error_message: string | null;
    generated_at: string | null;
    completed_at: string | null;
    failed_at: string | null;
    created_at: string;
    updated_at: string;
    template?: ReportTemplate;
    generated_by?: UserSummary;
    created_by?: UserSummary;
    updated_by?: UserSummary;
};

interface Props {
    report: ReportDetail;
    canDownload: boolean;
    canRegenerate: boolean;
    canDelete: boolean;
}

const statusStyles = {
    pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    processing: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const statusLabels = {
    pending: 'Menunggu',
    processing: 'Sedang Diproses',
    completed: 'Selesai',
    failed: 'Gagal',
};

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

function formatSize(bytes: number | null) {
    if (!bytes) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unit = 0;
    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit += 1;
    }
    return `${size.toFixed(unit === 0 ? 0 : 2)} ${units[unit]}`;
}

function displayParameter(value: unknown) {
    if (value === null || value === undefined || value === '') return 'Semua';
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

export default function Show({ report, canDownload, canRegenerate, canDelete }: Props) {
    const regenerate = () => {
        if (window.confirm('Generate ulang laporan ini dengan parameter yang sama?')) {
            router.post(route('saved-reports.regenerate', report.id));
        }
    };

    const destroy = () => {
        if (window.confirm('Hapus laporan ini? Aksi ini tidak dapat dibatalkan.')) {
            router.delete(route('saved-reports.destroy', report.id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={report.name} />

            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${statusStyles[report.status]}`}>
                                {statusLabels[report.status]}
                            </span>
                            <h1 className="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">{report.name}</h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{report.template?.name ?? 'Template tidak tersedia'}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('saved-reports.index')}><SecondaryButton>← Kembali</SecondaryButton></Link>
                            {canRegenerate && <SecondaryButton type="button" onClick={regenerate}>Generate Ulang</SecondaryButton>}
                            {canDownload && <Link href={route('saved-reports.download', report.id)}><PrimaryButton>Download {report.format.toUpperCase()}</PrimaryButton></Link>}
                        </div>
                    </header>

                    {report.status === 'failed' && report.error_message && (
                        <section className="rounded-lg border border-red-200 bg-red-50 p-5 dark:border-red-800 dark:bg-red-950" role="alert">
                            <h2 className="font-semibold text-red-800 dark:text-red-200">Generate laporan gagal</h2>
                            <p className="mt-1 whitespace-pre-wrap text-sm text-red-700 dark:text-red-300">{report.error_message}</p>
                        </section>
                    )}

                    {(report.status === 'pending' || report.status === 'processing') && (
                        <section className="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200" role="status">
                            Laporan sedang diproses oleh queue worker. Muat ulang halaman untuk melihat status terbaru.
                        </section>
                    )}

                    <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            ['Format', report.format.toUpperCase()],
                            ['Ukuran File', formatSize(report.file_size)],
                            ['Mulai Generate', formatDate(report.generated_at)],
                            ['Selesai', formatDate(report.completed_at)],
                        ].map(([label, value]) => (
                            <div key={label} className="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                                <p className="text-sm text-gray-500 dark:text-gray-400">{label}</p>
                                <p className="mt-2 font-semibold text-gray-900 dark:text-gray-100">{value}</p>
                            </div>
                        ))}
                    </section>

                    <section className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 className="font-semibold text-gray-900 dark:text-gray-100">Parameter Laporan</h2>
                        </div>
                        <dl className="grid gap-x-6 gap-y-5 p-6 sm:grid-cols-2">
                            {Object.entries(report.parameters ?? {}).map(([key, value]) => (
                                <div key={key}>
                                    <dt className="text-sm text-gray-500 dark:text-gray-400">{key.replaceAll('_', ' ')}</dt>
                                    <dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{displayParameter(value)}</dd>
                                </div>
                            ))}
                            {Object.keys(report.parameters ?? {}).length === 0 && (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Tidak ada parameter tersimpan.</p>
                            )}
                        </dl>
                    </section>

                    <section className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h2 className="font-semibold text-gray-900 dark:text-gray-100">Audit Metadata</h2>
                        <dl className="mt-4 grid gap-5 sm:grid-cols-2">
                            <div><dt className="text-sm text-gray-500 dark:text-gray-400">Dibuat oleh</dt><dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{report.created_by?.name ?? report.generated_by?.name ?? '—'}</dd></div>
                            <div><dt className="text-sm text-gray-500 dark:text-gray-400">Dibuat pada</dt><dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{formatDate(report.created_at)}</dd></div>
                            <div><dt className="text-sm text-gray-500 dark:text-gray-400">Diperbarui oleh</dt><dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{report.updated_by?.name ?? '—'}</dd></div>
                            <div><dt className="text-sm text-gray-500 dark:text-gray-400">Diperbarui pada</dt><dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{formatDate(report.updated_at)}</dd></div>
                        </dl>
                    </section>

                    {canDelete && (
                        <section className="flex justify-end border-t border-gray-200 pt-6 dark:border-gray-700">
                            <button type="button" onClick={destroy} className="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-950">
                                Hapus Laporan
                            </button>
                        </section>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
