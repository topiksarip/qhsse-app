import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, Ncr } from '@/types';
import SourceBadge from '@/Components/Quality/SourceBadge';
import StatusBadge from '@/Components/Quality/StatusBadge';
import SeverityBadge from '@/Components/Quality/SeverityBadge';

interface ShowProps extends PageProps {
    ncr: Ncr;
}

function fmtDate(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export default function Show({ auth, ncr }: ShowProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canUpdate = permissions.has('quality.ncrs.update');
    const canEdit = canUpdate && (ncr.status === 'open' || ncr.status === 'under_review');

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href={route('quality.ncrs.index')} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Kembali ke Daftar</Link>
                    <h2 className="mt-1 text-xl font-semibold text-gray-800 dark:text-gray-200">Non-Conformance Report</h2>
                </div>
            }
        >
            <Head title={`NCR ${ncr.ncr_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    {/* Header card */}
                    <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">{ncr.ncr_number}</span>
                                    <SeverityBadge severity={ncr.severity} />
                                    <StatusBadge status={ncr.status} />
                                    <SourceBadge source={ncr.source} />
                                </div>
                                <h3 className="mt-2 text-lg font-medium text-gray-900 dark:text-gray-100">{ncr.title}</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Dibuat: {fmtDate(ncr.created_at)}</p>
                            </div>
                            {canEdit && (
                                <Link href={route('quality.ncrs.edit', ncr.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                    ✏ Edit
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 space-y-6">
                        {/* Detail */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">DETAIL</h3>
                            <p className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{ncr.description || '-'}</p>
                            <dl className="mt-4 grid grid-cols-1 gap-3 border-t border-gray-200 pt-4 text-sm sm:grid-cols-2 dark:border-gray-700">
                                <div><dt className="text-gray-500 dark:text-gray-400">Site</dt><dd className="text-gray-900 dark:text-gray-100">{ncr.site?.name ?? '-'}</dd></div>
                                <div><dt className="text-gray-500 dark:text-gray-400">Departemen</dt><dd className="text-gray-900 dark:text-gray-100">{ncr.department?.name ?? '-'}</dd></div>
                                <div><dt className="text-gray-500 dark:text-gray-400">Produk/Jasa</dt><dd className="text-gray-900 dark:text-gray-100">{ncr.product_service || '-'}</dd></div>
                                <div><dt className="text-gray-500 dark:text-gray-400">Batch/Lot</dt><dd className="text-gray-900 dark:text-gray-100">{ncr.batch_lot || '-'}</dd></div>
                                <div><dt className="text-gray-500 dark:text-gray-400">Nama Pelanggan</dt><dd className="text-gray-900 dark:text-gray-100">{ncr.customer_name || '-'}</dd></div>
                                {ncr.closed_at && <div><dt className="text-gray-500 dark:text-gray-400">Ditutup</dt><dd className="text-gray-900 dark:text-gray-100">{fmtDate(ncr.closed_at)}</dd></div>}
                            </dl>
                        </div>

                        {/* RCA */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ANALISIS & TINDAKAN</h3>
                            <Section label="Akar Masalah (Root Cause)">{ncr.root_cause || 'Belum diisi'}</Section>
                            <Section label="Tindakan Korektif">{ncr.corrective_action || 'Belum diisi'}</Section>
                            <Section label="Tindakan Preventif">{ncr.preventive_action || 'Belum diisi'}</Section>
                        </div>

                        {/* CAPA Link */}
                        <div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">CAPA TERKAIT</h3>
                            {ncr.capaAction ? (
                                <div className="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <div>
                                        <span className="font-mono text-sm">{ncr.capaAction.number}</span>
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{ncr.capaAction.title}</p>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Status: {ncr.capaAction.status}</p>
                                    </div>
                                    <Link href={route('capa.actions.show', ncr.capaAction.id)} className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
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

function Section({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="mb-4 last:mb-0">
            <dt className="mb-1 text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{label}</dt>
            <dd className="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{children}</dd>
        </div>
    );
}
