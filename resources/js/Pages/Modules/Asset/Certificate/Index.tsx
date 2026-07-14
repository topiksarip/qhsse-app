import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
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
    can_update: boolean;
}

interface Asset {
    id: number;
    asset_number: string;
    name: string;
}

export default function Index({ asset, certificates, can }: PageProps<{
    asset: Asset;
    certificates: Certificate[];
    can: { create: boolean };
}>) {
    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            valid: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expiring_critical: 'bg-orange-100 text-orange-800',
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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <Link href={`/assets/${asset.id}`} className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block">
                            ← Back to Asset
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Certificates for {asset.asset_number} - {asset.name}
                        </h2>
                    </div>
                    {can.create && (
                        <Link
                            href={`/assets/${asset.id}/certificates/create`}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                        >
                            Add Certificate
                        </Link>
                    )}
                </div>
            }
        >
            <Head title={`Certificates - ${asset.asset_number}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issued</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {certificates.map((cert) => (
                                        <tr key={cert.id}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <Link
                                                    href={`/assets/${asset.id}/certificates/${cert.id}`}
                                                    className="text-blue-600 hover:text-blue-900"
                                                >
                                                    {cert.certificate_type}
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">{cert.certificate_number}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{formatDateOnly(cert.issued_date, '-')}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{formatDateOnly(cert.expiry_date, '-')}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(cert.status)}`}>
                                                    {getStatusLabel(cert.status)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                                <Link
                                                    href={`/assets/${asset.id}/certificates/${cert.id}`}
                                                    className="text-blue-600 hover:text-blue-900"
                                                >
                                                    View
                                                </Link>
                                                {cert.can_update && (
                                                    <Link
                                                        href={`/assets/${asset.id}/certificates/${cert.id}/edit`}
                                                        className="text-yellow-600 hover:text-yellow-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {certificates.length === 0 && (
                                <div className="py-12">
                                    <EmptyState
                                        title="No certificates added yet"
                                        description="Manage asset certificates, calibration records, and compliance documentation"
                                        action={
                                            can.create ? (
                                                <Link
                                                    href={`/assets/${asset.id}/certificates/create`}
                                                    className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                >
                                                    Add First Certificate
                                                </Link>
                                            ) : undefined
                                        }
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
