import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import { formatDateOnly } from '@/Utils/date';

interface Certificate {
    id: number;
    certificate_type: string;
    certificate_number: string;
    issued_date: string | null;
    expiry_date: string | null;
    status: string;
    issuing_body: string | null;
    notes: string | null;
    certificate_file: {
        id: number;
        original_name: string;
        size: number;
        mime_type: string;
        download_url: string;
    } | null;
    created_at: string;
}

interface Asset {
    id: number;
    asset_number: string;
    name: string;
}

export default function Show({ asset, certificate, can }: PageProps<{
    asset: Asset;
    certificate: Certificate;
    can: { update: boolean; delete: boolean };
}>) {
    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            valid: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expiring_critical: 'bg-amber-100 text-amber-800',
            expired: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            valid: 'Valid',
            expiring_soon: 'Expiring Soon',
            expiring_critical: 'Expiring Critical',
            expired: 'Expired',
        };
        return labels[status] || status;
    };

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <Link
                            href={`/assets/${asset.id}/certificates`}
                            className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block"
                        >
                            ← Back to Certificates
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Certificate: {certificate.certificate_number}
                        </h2>
                    </div>
                    <div className="flex space-x-2">
                        {can.update && (
                            <Link
                                href={`/assets/${asset.id}/certificates/${certificate.id}/edit`}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                            >
                                Edit
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Certificate ${certificate.certificate_number}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Certificate Details */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-medium mb-4">Certificate Details</h3>
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Certificate Type</h4>
                                    <p className="mt-1 text-sm text-gray-900">{certificate.certificate_type}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Certificate Number</h4>
                                    <p className="mt-1 text-sm text-gray-900">{certificate.certificate_number}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Status</h4>
                                    <span className={`mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(certificate.status)}`}>
                                        {getStatusLabel(certificate.status)}
                                    </span>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Lembaga Penerbit</h4>
                                    <p className="mt-1 text-sm text-gray-900">{certificate.issuing_body || '-'}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Tanggal Terbit</h4>
                                    <p className="mt-1 text-sm text-gray-900">{formatDateOnly(certificate.issued_date, '-')}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Tanggal Kedaluwarsa</h4>
                                    <p className="mt-1 text-sm text-gray-900">{formatDateOnly(certificate.expiry_date, '-')}</p>
                                </div>
                            </div>

                            {certificate.notes && (
                                <div className="mt-6">
                                    <h4 className="text-sm font-medium text-gray-500">Notes</h4>
                                    <p className="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{certificate.notes}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Attached Files */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium mb-4">Attached Files</h3>
                            {certificate.certificate_file ? (
                                <div className="space-y-3">
                                        <div className="flex items-center justify-between border rounded-lg p-4">
                                            <div>
                                                <p className="font-medium text-sm">{certificate.certificate_file.original_name}</p>
                                                <p className="text-xs text-gray-500">
                                                    {formatFileSize(certificate.certificate_file.size)} • {certificate.certificate_file.mime_type}
                                                </p>
                                            </div>
                                            <a
                                                href={certificate.certificate_file.download_url}
                                                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                                            >
                                                Download
                                            </a>
                                        </div>
                                </div>
                            ) : (
                                <p className="text-gray-500 text-center py-8">No files attached.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
