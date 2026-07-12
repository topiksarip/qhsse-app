import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ReportTemplate } from '@/types/modules/reporting';
import { Head, Link } from '@inertiajs/react';

type UserSummary = { id: number; name: string; email: string };
type TemplateDetail = Omit<ReportTemplate, 'created_by' | 'updated_by'> & {
    created_by?: UserSummary;
    updated_by?: UserSummary;
};

const typeLabels: Record<string, string> = {
    incident_summary: 'Ringkasan Insiden',
    capa_summary: 'Ringkasan CAPA',
    inspection_summary: 'Ringkasan Inspeksi',
    audit_summary: 'Ringkasan Audit',
    training_compliance: 'Kepatuhan Pelatihan',
    monthly_qhsse: 'Laporan Bulanan QHSSE',
    annual_qhsse: 'Laporan Tahunan QHSSE',
    custom: 'Laporan Custom',
};

function formatDate(value: string) {
    return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

export default function Show({ template }: { template: TemplateDetail }) {
    const sections = template.config?.sections ?? [];

    return (
        <AuthenticatedLayout>
            <Head title={template.name} />

            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {typeLabels[template.type] ?? template.type}
                                </span>
                                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${template.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'}`}>
                                    {template.is_active ? 'Aktif' : 'Nonaktif'}
                                </span>
                                {template.is_predefined && <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">Template Bawaan</span>}
                            </div>
                            <h1 className="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">{template.name}</h1>
                            <p className="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">{template.description || 'Tidak ada deskripsi.'}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('report-templates.index')}><SecondaryButton>← Kembali</SecondaryButton></Link>
                            {!template.is_predefined && <Link href={route('report-templates.edit', template.id)}><SecondaryButton>Edit</SecondaryButton></Link>}
                            {template.is_active && <Link href={route('saved-reports.create', { template_id: template.id })}><PrimaryButton>Generate Laporan</PrimaryButton></Link>}
                        </div>
                    </header>

                    <section className="grid gap-4 sm:grid-cols-3">
                        <div className="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Laporan tersimpan</p>
                            <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{template.saved_reports_count ?? 0}</p>
                        </div>
                        <div className="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Section aktif</p>
                            <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{sections.filter((section) => section.enabled).length}</p>
                        </div>
                        <div className="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Format didukung</p>
                            <p className="mt-2 text-lg font-bold text-gray-900 dark:text-gray-100">CSV · PDF · Excel</p>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 className="font-semibold text-gray-900 dark:text-gray-100">Konfigurasi Section</h2>
                        </div>
                        {sections.length === 0 ? (
                            <p className="p-8 text-center text-sm text-gray-500 dark:text-gray-400">Template ini belum memiliki konfigurasi section.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            {['Label', 'Key', 'Sumber Data', 'Status'].map((label) => <th key={label} className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</th>)}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {sections.map((section) => (
                                            <tr key={section.key}>
                                                <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{section.label}</td>
                                                <td className="px-6 py-4 font-mono text-sm text-gray-600 dark:text-gray-300">{section.key}</td>
                                                <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{section.data_source || '—'}</td>
                                                <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{section.enabled ? 'Aktif' : 'Nonaktif'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>

                    <section className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h2 className="font-semibold text-gray-900 dark:text-gray-100">Audit Metadata</h2>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><dt className="text-sm text-gray-500 dark:text-gray-400">Dibuat oleh</dt><dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{template.created_by?.name ?? '—'} · {formatDate(template.created_at)}</dd></div>
                            <div><dt className="text-sm text-gray-500 dark:text-gray-400">Diperbarui oleh</dt><dd className="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{template.updated_by?.name ?? '—'} · {formatDate(template.updated_at)}</dd></div>
                        </dl>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
