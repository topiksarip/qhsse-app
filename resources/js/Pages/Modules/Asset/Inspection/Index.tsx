import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';

interface Inspection {
    id: number;
    inspection_date: string;
    result: string;
    inspector: { id: number; name: string };
    findings: string | null;
    recommendations: string | null;
    capa_action_id: number | null;
    created_at: string;
}

interface Asset {
    id: number;
    asset_number: string;
    name: string;
}

export default function Index({ auth, asset, inspections, can }: PageProps<{
    asset: Asset;
    inspections: Inspection[];
    can: { create: boolean; update: boolean; linkCapa: boolean };
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
                        <Link href={`/assets/${asset.id}`} className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block">
                            ← Back to Asset
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Inspections for {asset.asset_number} - {asset.name}
                        </h2>
                    </div>
                    {can.create && (
                        <Link
                            href={`/assets/${asset.id}/inspections/create`}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                        >
                            Add Inspection
                        </Link>
                    )}
                </div>
            }
        >
            <Head title={`Inspections - ${asset.asset_number}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inspector</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CAPA</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {inspections.map((inspection) => (
                                        <tr key={inspection.id}>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <Link
                                                    href={`/assets/${asset.id}/inspections/${inspection.id}`}
                                                    className="text-blue-600 hover:text-blue-900"
                                                >
                                                    {inspection.inspection_date}
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">{inspection.inspector.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${getResultColor(inspection.result)}`}>
                                                    {getResultLabel(inspection.result)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {inspection.capa_action_id ? (
                                                    <span className="text-green-600 text-sm">✓ Linked</span>
                                                ) : (
                                                    <span className="text-gray-400 text-sm">-</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                                <Link
                                                    href={`/assets/${asset.id}/inspections/${inspection.id}`}
                                                    className="text-blue-600 hover:text-blue-900"
                                                >
                                                    View
                                                </Link>
                                                {can.update && (
                                                    <Link
                                                        href={`/assets/${asset.id}/inspections/${inspection.id}/edit`}
                                                        className="text-yellow-600 hover:text-yellow-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                )}
                                                {can.linkCapa && !inspection.capa_action_id && inspection.result !== 'pass' && (
                                                    <Link
                                                        href={`/assets/${asset.id}/inspections/${inspection.id}/link-capa`}
                                                        method="post"
                                                        as="button"
                                                        className="text-orange-600 hover:text-orange-900"
                                                    >
                                                        Link CAPA
                                                    </Link>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {inspections.length === 0 && (
                                <div className="py-12">
                                    <EmptyState
                                        title="No inspections recorded yet"
                                        description="Track asset inspections, maintenance results, and CAPA actions"
                                        action={
                                            can.create ? (
                                                <Link
                                                    href={`/assets/${asset.id}/inspections/create`}
                                                    className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                >
                                                    Add First Inspection
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
