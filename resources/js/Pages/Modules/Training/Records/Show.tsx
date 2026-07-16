import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { Head, Link } from '@inertiajs/react';
import { PageProps, TrainingRecord } from '@/types';
import StatusBadge from '@/Components/Training/StatusBadge';
import ResultBadge from '@/Components/Training/ResultBadge';
import ExpiryIndicator from '@/Components/Training/ExpiryIndicator';
import { format, parseISO } from 'date-fns';

interface ShowProps extends PageProps {
    record: TrainingRecord;
    can: {
        update: boolean;
        delete: boolean;
    };
}

export default function Show({ auth, record, can }: ShowProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            Detail Record Pelatihan
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {record.training_number}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('training.records.index')}
                            className="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                        >
                            ← Kembali
                        </Link>
                        {can.update && (
                            <Link
                                href={route('training.records.edit', record.id)}
                                className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                            >
                                ✏ Edit
                            </Link>
                        )}
                        <DeleteWithConfirm
                            routeName="training.records.destroy"
                            id={record.id}
                            permission="training.records.delete"
                            itemLabel={record.training_number}
                            redirectTo="training.records.index"
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:text-white"
                        >
                            Hapus
                        </DeleteWithConfirm>
                    </div>
                </div>
            }
        >
            <Head title={`Record: ${record.training_number}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Status Overview Card */}
                    <div className="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm opacity-90">Nomor Record</p>
                                <h3 className="text-2xl font-bold mt-1">{record.training_number}</h3>
                            </div>
                            <div className="text-right">
                                <StatusBadge status={record.status} />
                                <div className="mt-2">
                                    <ResultBadge result={record.result} />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Employee & Program Info */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Employee Card */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                👤 KARYAWAN
                            </h3>
                            <div className="space-y-3">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Nama</p>
                                    <p className="text-base font-medium text-gray-900 dark:text-gray-100 mt-1">
                                        {record.employee?.name || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">NIK</p>
                                    <p className="text-base font-mono text-gray-900 dark:text-gray-100 mt-1">
                                        {record.employee?.employee_no || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Site</p>
                                    <p className="text-base text-gray-900 dark:text-gray-100 mt-1">
                                        {record.employee?.site?.name || 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Program Card */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                📚 PROGRAM PELATIHAN
                            </h3>
                            <div className="space-y-3">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Nama Program</p>
                                    <p className="text-base font-medium text-gray-900 dark:text-gray-100 mt-1">
                                        {record.training_program?.name || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Kode</p>
                                    <p className="text-base font-mono text-gray-900 dark:text-gray-100 mt-1">
                                        {record.training_program?.code || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Kategori</p>
                                    <p className="text-base text-gray-900 dark:text-gray-100 mt-1">
                                        {record.training_program?.category || 'N/A'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Durasi</p>
                                    <p className="text-base text-gray-900 dark:text-gray-100 mt-1">
                                        {record.training_program?.duration_hours || 0} jam
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Training Details */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            DETAIL PELATIHAN
                        </h3>
                        <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                {/* Provider */}
                                {record.provider && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Provider / Penyelenggara
                                        </dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {record.provider}
                                        </dd>
                                    </div>
                                )}

                                {/* Start Date */}
                                <div>
                                    <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Tanggal Mulai
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        📅 {format(parseISO(record.start_date), 'dd MMMM yyyy')}
                                    </dd>
                                </div>

                                {/* End Date */}
                                {record.end_date && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Tanggal Selesai
                                        </dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            📅 {format(parseISO(record.end_date), 'dd MMMM yyyy')}
                                        </dd>
                                    </div>
                                )}

                                {/* Status */}
                                <div>
                                    <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Status
                                    </dt>
                                    <dd className="mt-1">
                                        <StatusBadge status={record.status} />
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Results & Certificate */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            HASIL & SERTIFIKAT
                        </h3>
                        <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                {/* Score */}
                                {record.score !== null && record.score !== undefined && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Nilai / Score
                                        </dt>
                                        <dd className="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                            {record.score}
                                        </dd>
                                    </div>
                                )}

                                {/* Result */}
                                {record.result && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Hasil
                                        </dt>
                                        <dd className="mt-1">
                                            <ResultBadge result={record.result} />
                                        </dd>
                                    </div>
                                )}

                                {/* Certificate Number */}
                                {record.certificate_number && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Nomor Sertifikat
                                        </dt>
                                        <dd className="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">
                                            {record.certificate_number}
                                        </dd>
                                    </div>
                                )}

                                {/* Expiry Date */}
                                {record.expiry_date && (
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            Masa Berlaku s/d
                                        </dt>
                                        <dd className="mt-1">
                                            <ExpiryIndicator 
                                                expiryDate={record.expiry_date} 
                                                status={record.status} 
                                            />
                                        </dd>
                                    </div>
                                )}

                                {/* Certificate File */}
                                {record.certificate_file && (
                                    <div className="md:col-span-2">
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                                            File Sertifikat
                                        </dt>
                                        <dd>
                                            <div className="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600">
                                                <div className="flex-shrink-0">
                                                    <svg
                                                        className="w-8 h-8 text-indigo-500"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                        />
                                                    </svg>
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        📎 {record.certificate_file.file_name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {(record.certificate_file.file_size / 1024).toFixed(2)} KB
                                                    </p>
                                                </div>
                                                <a
                                                    href={route('files.download', record.certificate_file.id)}
                                                    className="flex-shrink-0 px-3 py-1 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
                                                >
                                                    📥 Download
                                                </a>
                                            </div>
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                    </div>

                    {/* PPE Fit-Test & APD Link */}
                    {record.training_type && (record.training_type === 'ppe_fit_test' || record.apd_item || record.fit_test_result) && (
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                FIT-TEST APD
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <dl className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">Jenis Pelatihan</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {record.training_type === 'ppe_fit_test' ? 'Fit-Test APD' : record.training_type}
                                        </dd>
                                    </div>
                                    {record.apd_item && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">Item APD</dt>
                                            <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                                <Link href={route('apd.items.show', record.apd_item.id)} className="text-indigo-600 hover:underline dark:text-indigo-400">
                                                    {record.apd_item.item_number} {record.apd_item.catalog?.name ? `— ${record.apd_item.catalog.name}` : ''}
                                                </Link>
                                            </dd>
                                        </div>
                                    )}
                                    {record.fit_test_result && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-600 dark:text-gray-400">Hasil Fit-Test</dt>
                                            <dd className="mt-1">
                                                <ResultBadge result={record.fit_test_result} />
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                            </div>
                        </div>
                    )}

                    {/* Notes */}
                    {record.notes && (
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                CATATAN
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    {record.notes}
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Metadata */}
                    <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Dibuat: {format(parseISO(record.created_at), 'dd MMM yyyy HH:mm')}</span>
                            <span>Diperbarui: {format(parseISO(record.updated_at), 'dd MMM yyyy HH:mm')}</span>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
