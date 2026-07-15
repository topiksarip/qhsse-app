import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import { formatDateOnly } from '@/Utils/date';

interface Certificate { id: number; certificate_type: string; certificate_number: string; issued_date: string | null; expiry_date: string | null; status: string; issuing_body: string | null; notes: string | null; can_update: boolean }
interface Asset { id: number; asset_number: string; name: string }

export default function Index({ asset, certificates, can }: PageProps<{ asset: Asset; certificates: Certificate[]; can: { create: boolean } }>) {
    const statusColor = (s: string) => ({ valid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', expiring_soon: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200', expiring_critical: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200', expired: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }[s] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200');
    const statusLabel = (s: string) => ({ valid: 'Valid', expiring_soon: 'Expiring Soon', expiring_critical: 'Expiring Critical', expired: 'Expired' }[s] || s);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link href={`/assets/${asset.id}`} className="mb-2 inline-block text-sm text-emerald-600 hover:underline dark:text-emerald-400">← Back to Asset</Link>
                        <h2 className="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">Certificates for {asset.asset_number} - {asset.name}</h2>
                    </div>
                    {can.create && <PrimaryButton size="sm" href={`/assets/${asset.id}/certificates/create`}>Add Certificate</PrimaryButton>}
                </div>
            }
        >
            <Head title={`Certificates - ${asset.asset_number}`} />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Type</th>
                                <th className="px-4 py-3">Number</th>
                                <th className="px-4 py-3">Issued</th>
                                <th className="px-4 py-3">Expiry</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {certificates.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12">
                                        <EmptyState title="No certificates added yet" description="Manage asset certificates, calibration records, and compliance documentation" action={can.create ? <PrimaryButton href={`/assets/${asset.id}/certificates/create`}>Add First Certificate</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : certificates.map((cert) => (
                                <tr key={cert.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><Link href={`/assets/${asset.id}/certificates/${cert.id}`} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{cert.certificate_type}</Link></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-800 dark:text-slate-200">{cert.certificate_number}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{formatDateOnly(cert.issued_date, '-')}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{formatDateOnly(cert.expiry_date, '-')}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm"><span className={`rounded-full px-2 py-1 text-xs font-semibold ${statusColor(cert.status)}`}>{statusLabel(cert.status)}</span></td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={`/assets/${asset.id}/certificates/${cert.id}`} className="text-emerald-600 hover:underline dark:text-emerald-400">View</Link>
                                        {cert.can_update && <Link href={`/assets/${asset.id}/certificates/${cert.id}/edit`} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>}
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
