import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import { formatDateOnly } from '@/Utils/date';

interface Inspection { id: number; inspection_date: string; result: string; inspector: { id: number; name: string }; findings: string | null; capa_action: { id: number; action_number: string; status: string } | null; can_update: boolean; can_create_capa: boolean; created_at: string }
interface Asset { id: number; asset_number: string; name: string }

export default function Index({ asset, inspections, can }: PageProps<{ asset: Asset; inspections: Inspection[]; can: { create: boolean; delete: boolean } }>) {
    const resultColor = (r: string) => ({ pass: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', fail: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', maintenance_required: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }[r] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200');
    const resultLabel = (r: string) => ({ pass: 'Pass', fail: 'Fail', maintenance_required: 'Maintenance Required' }[r] || r);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link href={`/assets/${asset.id}`} className="mb-2 inline-block text-sm text-emerald-600 hover:underline dark:text-emerald-400">← Back to Asset</Link>
                        <h2 className="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Inspections for {asset.asset_number} - {asset.name}</h2>
                    </div>
                    {can.create && <PrimaryButton size="sm" href={`/assets/${asset.id}/inspections/create`}>Add Inspection</PrimaryButton>}
                </div>
            }
        >
            <Head title={`Inspections - ${asset.asset_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Date</th>
                                <th className="px-4 py-3">Inspector</th>
                                <th className="px-4 py-3">Result</th>
                                <th className="px-4 py-3">CAPA</th>
                                <th className="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {inspections.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-12">
                                        <EmptyState title="No inspections recorded yet" description="Track asset inspections, maintenance results, and CAPA actions" action={can.create ? <PrimaryButton href={`/assets/${asset.id}/inspections/create`}>Add First Inspection</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : inspections.map((inspection) => (
                                <tr key={inspection.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><Link href={`/assets/${asset.id}/inspections/${inspection.id}`} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{formatDateOnly(inspection.inspection_date)}</Link></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-800 dark:text-slate-200">{inspection.inspector.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><span className={`rounded-full px-2 py-1 text-xs font-semibold ${resultColor(inspection.result)}`}>{resultLabel(inspection.result)}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm">
                                        {inspection.capa_action ? <Link href={route('capa.actions.show', inspection.capa_action.id)} className="text-green-600 text-sm hover:underline dark:text-green-400">{inspection.capa_action.action_number}</Link> : <span className="text-sm text-gray-400">-</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={`/assets/${asset.id}/inspections/${inspection.id}`} className="text-emerald-600 hover:underline dark:text-emerald-400">View</Link>
                                        {inspection.can_update && <Link href={`/assets/${asset.id}/inspections/${inspection.id}/edit`} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>}
                                        {inspection.can_create_capa && !inspection.capa_action && <Link href={`/assets/${asset.id}/inspections/${inspection.id}/create-capa`} className="ml-2 text-orange-600 hover:underline dark:text-orange-400">Buat CAPA</Link>}
                                        {can.delete && (
                                            <DeleteWithConfirm
                                                routeName="assets.inspections.destroy"
                                                id={[asset.id, inspection.id]}
                                                permission="asset.inspections.delete"
                                                itemLabel={formatDateOnly(inspection.inspection_date)}
                                                redirectTo="assets.inspections.index"
                                                redirectParams={{ asset: asset.id }}
                                                asLink
                                            >
                                                Delete
                                            </DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
