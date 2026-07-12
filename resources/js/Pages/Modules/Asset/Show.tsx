import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useState } from 'react';

interface Asset {
    id: number;
    asset_number: string;
    name: string;
    category: string;
    serial_number: string | null;
    model: string | null;
    manufacturer: string | null;
    status: string;
    safety_critical: boolean;
    purchase_date: string | null;
    installation_date: string | null;
    warranty_expiry_date: string | null;
    next_inspection_date: string | null;
    description: string | null;
    notes: string | null;
    site: { id: number; name: string };
    area: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    certificates: Array<{
        id: number;
        certificate_type: string;
        certificate_number: string;
        expiry_date: string | null;
        status: string;
    }>;
    inspections: Array<{
        id: number;
        inspection_date: string;
        result: string;
        inspector: { id: number; name: string };
        capa_action_id: number | null;
    }>;
    created_at: string;
}

export default function Show({ auth, asset, can }: PageProps<{
    asset: Asset;
    can: {
        update: boolean;
        delete: boolean;
        decommission: boolean;
        createCertificate: boolean;
        createInspection: boolean;
    };
}>) {
    const [activeTab, setActiveTab] = useState<'details' | 'certificates' | 'inspections'>('details');

    const getStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-yellow-100 text-yellow-800',
            decommissioned: 'bg-gray-100 text-gray-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getCertificateStatusColor = (status: string) => {
        const colors: Record<string, string> = {
            valid: 'bg-green-100 text-green-800',
            expiring_soon: 'bg-yellow-100 text-yellow-800',
            expiring_critical: 'bg-orange-100 text-orange-800',
            expired: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    const getInspectionResultColor = (result: string) => {
        const colors: Record<string, string> = {
            pass: 'bg-green-100 text-green-800',
            fail: 'bg-red-100 text-red-800',
            maintenance_required: 'bg-yellow-100 text-yellow-800',
        };
        return colors[result] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <Link href="/assets" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block">
                            ← Back to Assets
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {asset.asset_number} - {asset.name}
                            {asset.safety_critical && (
                                <span className="ml-3 px-3 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded-full">
                                    SAFETY CRITICAL
                                </span>
                            )}
                        </h2>
                    </div>
                    <div className="flex space-x-2">
                        {can.update && (
                            <Link
                                href={`/assets/${asset.id}/edit`}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                            >
                                Edit
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Asset ${asset.asset_number}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Tabs */}
                    <div className="mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    onClick={() => setActiveTab('details')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'details'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    Details
                                </button>
                                <button
                                    onClick={() => setActiveTab('certificates')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'certificates'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    Certificates ({asset.certificates.length})
                                </button>
                                <button
                                    onClick={() => setActiveTab('inspections')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                                        activeTab === 'inspections'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    Inspections ({asset.inspections.length})
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* Details Tab */}
                    {activeTab === 'details' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="grid grid-cols-2 gap-6">
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Asset Number</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.asset_number}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Status</h3>
                                        <span className={`mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(asset.status)}`}>
                                            {asset.status}
                                        </span>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Category</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.category}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Serial Number</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.serial_number || '-'}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Model</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.model || '-'}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Manufacturer</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.manufacturer || '-'}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Site</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.site.name}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Area</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.area?.name || '-'}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Department</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.department?.name || '-'}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Purchase Date</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.purchase_date || '-'}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Next Inspection</h3>
                                        <p className="mt-1 text-sm text-gray-900">{asset.next_inspection_date || '-'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Certificates Tab */}
                    {activeTab === 'certificates' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-medium">Certificates</h3>
                                    {can.createCertificate && (
                                        <Link
                                            href={`/assets/${asset.id}/certificates/create`}
                                            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                        >
                                            Add Certificate
                                        </Link>
                                    )}
                                </div>
                                <div className="space-y-4">
                                    {asset.certificates.map((cert) => (
                                        <div key={cert.id} className="border rounded-lg p-4">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <h4 className="font-medium">{cert.certificate_type}</h4>
                                                    <p className="text-sm text-gray-600">{cert.certificate_number}</p>
                                                    {cert.expiry_date && (
                                                        <p className="text-sm text-gray-600">Expires: {cert.expiry_date}</p>
                                                    )}
                                                </div>
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getCertificateStatusColor(cert.status)}`}>
                                                    {cert.status}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                    {asset.certificates.length === 0 && (
                                        <p className="text-gray-500 text-center py-8">No certificates added yet.</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Inspections Tab */}
                    {activeTab === 'inspections' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-medium">Inspections</h3>
                                    {can.createInspection && (
                                        <Link
                                            href={`/assets/${asset.id}/inspections/create`}
                                            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                        >
                                            Add Inspection
                                        </Link>
                                    )}
                                </div>
                                <div className="space-y-4">
                                    {asset.inspections.map((inspection) => (
                                        <div key={inspection.id} className="border rounded-lg p-4">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <p className="text-sm text-gray-600">Date: {inspection.inspection_date}</p>
                                                    <p className="text-sm text-gray-600">Inspector: {inspection.inspector.name}</p>
                                                    {inspection.capa_action_id && (
                                                        <p className="text-sm text-blue-600">CAPA Action linked</p>
                                                    )}
                                                </div>
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getInspectionResultColor(inspection.result)}`}>
                                                    {inspection.result}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                    {asset.inspections.length === 0 && (
                                        <p className="text-gray-500 text-center py-8">No inspections recorded yet.</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
