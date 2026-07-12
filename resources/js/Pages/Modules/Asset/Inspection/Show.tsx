import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';

interface Inspection {
    id: number;
    inspection_date: string;
    result: string;
    findings: string | null;
    recommendations: string | null;
    notes: string | null;
    inspector: { id: number; name: string };
    capa_action: {
        id: number;
        action_number: string;
        title: string;
        status: string;
    } | null;
    created_at: string;
}

interface Asset {
    id: number;
    asset_number: string;
    name: string;
}

export default function Show({ auth, asset, inspection, can }: PageProps<{
    asset: Asset;
    inspection: Inspection;
    can: { update: boolean; delete: boolean; linkCapa: boolean };
}>) {
    const getResultColor = (result: string) => {
        const colors: Record<string, string> = {
            pass: 'bg-green-100 text-green-800',
            fail: 'bg-red-100 text-red-800',
            maintenance_required: 'bg-yellow-100 text-yellow-800',
        };
        return colors[result] || 'bg-gray-100 text-gray-800';
    };

    const getResultLabel = (result: string) => {
        const labels: Record<string, string> = {
            pass: 'Pass',
            fail: 'Fail',
            maintenance_required: 'Maintenance Required',
        };
        return labels[result] || result;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <Link
                            href={`/assets/${asset.id}/inspections`}
                            className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block"
                        >
                            ← Back to Inspections
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Inspection: {inspection.inspection_date}
                        </h2>
                    </div>
                    <div className="flex space-x-2">
                        {can.update && (
                            <Link
                                href={`/assets/${asset.id}/inspections/${inspection.id}/edit`}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                            >
                                Edit
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Inspection ${inspection.inspection_date}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Inspection Details */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-medium mb-4">Inspection Details</h3>
                            <div className="grid grid-cols-2 gap-6">
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Inspection Date</h4>
                                    <p className="mt-1 text-sm text-gray-900">{inspection.inspection_date}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Result</h4>
                                    <span className={`mt-1 inline-block px-2 py-1 text-xs font-semibold rounded-full ${getResultColor(inspection.result)}`}>
                                        {getResultLabel(inspection.result)}
                                    </span>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Inspector</h4>
                                    <p className="mt-1 text-sm text-gray-900">{inspection.inspector.name}</p>
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500">Recorded</h4>
                                    <p className="mt-1 text-sm text-gray-900">{inspection.created_at}</p>
                                </div>
                            </div>

                            {inspection.findings && (
                                <div className="mt-6">
                                    <h4 className="text-sm font-medium text-gray-500">Findings</h4>
                                    <p className="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{inspection.findings}</p>
                                </div>
                            )}

                            {inspection.recommendations && (
                                <div className="mt-6">
                                    <h4 className="text-sm font-medium text-gray-500">Recommendations</h4>
                                    <p className="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{inspection.recommendations}</p>
                                </div>
                            )}

                            {inspection.notes && (
                                <div className="mt-6">
                                    <h4 className="text-sm font-medium text-gray-500">Additional Notes</h4>
                                    <p className="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{inspection.notes}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* CAPA Action Link */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium mb-4">CAPA Action</h3>
                            {inspection.capa_action ? (
                                <div className="border rounded-lg p-4">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-medium">
                                                <Link
                                                    href={`/capa/${inspection.capa_action.id}`}
                                                    className="text-blue-600 hover:text-blue-900"
                                                >
                                                    {inspection.capa_action.action_number}
                                                </Link>
                                            </p>
                                            <p className="text-sm text-gray-600">{inspection.capa_action.title}</p>
                                            <p className="text-sm text-gray-500">Status: {inspection.capa_action.status}</p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <p className="text-gray-500 mb-4">No CAPA action linked yet.</p>
                                    {can.linkCapa && inspection.result !== 'pass' && (
                                        <Link
                                            href={`/assets/${asset.id}/inspections/${inspection.id}/link-capa`}
                                            method="post"
                                            as="button"
                                            className="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700"
                                        >
                                            Link to CAPA Action
                                        </Link>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
