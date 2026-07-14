import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { formatDateOnly } from '@/Utils/date';
import { FormEvent, useState } from 'react';

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
        capa_action: { id: number; action_number: string; status: string } | null;
    }>;
    created_at: string;
}

interface Comment {
    id: number;
    body: string;
    created_at: string;
    author: { id: number; name: string } | null;
}

interface Activity {
    id: number;
    event: string;
    description: string | null;
    actor_name: string | null;
    created_at: string;
}

interface AuditLog {
    id: number;
    event: string;
    actor_name: string | null;
    created_at: string;
}

type Tab = 'details' | 'certificates' | 'inspections' | 'history';

export default function Show({ asset, comments, activities, auditLogs, can }: PageProps<{
    asset: Asset;
    comments: Comment[];
    activities: Activity[];
    auditLogs: AuditLog[];
    can: {
        update: boolean;
        decommission: boolean;
        changeStatus: boolean;
        comment: boolean;
        createCertificate: boolean;
        createInspection: boolean;
    };
}>) {
    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    const initialTab: Tab = ['certificates', 'inspections', 'history'].includes(requestedTab ?? '')
        ? requestedTab as Tab
        : 'details';
    const [activeTab, setActiveTab] = useState<Tab>(initialTab);
    const commentForm = useForm({ body: '' });

    const changeStatus = () => {
        const status = asset.status === 'active' ? 'inactive' : 'active';
        if (window.confirm(`Ubah status aset menjadi ${status}?`)) {
            router.patch(`/assets/${asset.id}/status`, { status }, { preserveScroll: true });
        }
    };

    const decommission = () => {
        if (window.confirm('Decommission bersifat terminal. Lanjutkan?')) {
            router.post(`/assets/${asset.id}/decommission`, {}, { preserveScroll: true });
        }
    };

    const submitComment = (event: FormEvent) => {
        event.preventDefault();
        commentForm.post(`/assets/${asset.id}/comments`, {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    };

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
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
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
                    <div className="flex flex-wrap gap-2">
                        {can.changeStatus && (
                            <button type="button" onClick={changeStatus} className="inline-flex items-center rounded-md border border-amber-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-amber-700 hover:bg-amber-50">
                                {asset.status === 'active' ? 'Nonaktifkan' : 'Aktifkan'}
                            </button>
                        )}
                        {can.decommission && (
                            <button type="button" onClick={decommission} className="inline-flex items-center rounded-md bg-red-700 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-800">
                                Decommission
                            </button>
                        )}
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
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Tabs */}
                    <div className="mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex min-w-max space-x-8 overflow-x-auto">
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
                                <button
                                    onClick={() => setActiveTab('history')}
                                    className={`py-4 px-1 border-b-2 font-medium text-sm ${activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`}
                                >
                                    Riwayat & Komentar
                                </button>
                            </nav>
                        </div>
                    </div>

                    {/* Details Tab */}
                    {activeTab === 'details' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                        <p className="mt-1 text-sm text-gray-900">{formatDateOnly(asset.purchase_date, '-')}</p>
                                    </div>
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500">Next Inspection</h3>
                                        <p className="mt-1 text-sm text-gray-900">{formatDateOnly(asset.next_inspection_date, '-')}</p>
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
                                                    <Link href={`/assets/${asset.id}/certificates/${cert.id}`} className="font-medium text-blue-600 hover:underline">
                                                        {cert.certificate_type}
                                                    </Link>
                                                    <p className="text-sm text-gray-600">{cert.certificate_number}</p>
                                                    {cert.expiry_date && (
                                                        <p className="text-sm text-gray-600">Kedaluwarsa: {formatDateOnly(cert.expiry_date)}</p>
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
                                                    <p className="text-sm text-gray-600">
                                                        <Link href={`/assets/${asset.id}/inspections/${inspection.id}`} className="text-blue-600 hover:underline">
                                                            Inspeksi {formatDateOnly(inspection.inspection_date)}
                                                        </Link>
                                                    </p>
                                                    <p className="text-sm text-gray-600">Inspector: {inspection.inspector.name}</p>
                                                    {inspection.capa_action && (
                                                        <Link href={route('capa.actions.show', inspection.capa_action.id)} className="text-sm text-blue-600 hover:underline">
                                                            CAPA {inspection.capa_action.action_number}
                                                        </Link>
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

                    {activeTab === 'history' && (
                        <div className="grid gap-6 lg:grid-cols-2">
                            <section className="rounded-lg bg-white p-6 shadow-sm">
                                <h3 className="text-lg font-medium">Komentar</h3>
                                {can.comment && (
                                    <form onSubmit={submitComment} className="mt-4">
                                        <textarea
                                            value={commentForm.data.body}
                                            onChange={(event) => commentForm.setData('body', event.target.value)}
                                            rows={3}
                                            className="w-full rounded-md border-gray-300 shadow-sm"
                                            placeholder="Tambahkan komentar operasional..."
                                            required
                                        />
                                        {commentForm.errors.body && <p className="mt-1 text-sm text-red-600">{commentForm.errors.body}</p>}
                                        <button disabled={commentForm.processing} className="mt-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                            Kirim komentar
                                        </button>
                                    </form>
                                )}
                                <div className="mt-5 space-y-4">
                                    {comments.length === 0 && <p className="text-sm text-gray-500">Belum ada komentar.</p>}
                                    {comments.map((comment) => (
                                        <div key={comment.id} className="border-t pt-3">
                                            <p className="text-xs font-semibold text-gray-700">{comment.author?.name ?? 'Unknown'} · {new Date(comment.created_at).toLocaleString('id-ID')}</p>
                                            <p className="mt-1 whitespace-pre-wrap text-sm text-gray-700">{comment.body}</p>
                                        </div>
                                    ))}
                                </div>
                            </section>
                            <div className="space-y-6">
                                <section className="rounded-lg bg-white p-6 shadow-sm">
                                    <h3 className="text-lg font-medium">Activity Log</h3>
                                    <div className="mt-4 space-y-3">
                                        {activities.length === 0 && <p className="text-sm text-gray-500">Belum ada aktivitas.</p>}
                                        {activities.map((activity) => (
                                            <div key={activity.id} className="border-l-2 border-blue-300 pl-3 text-sm">
                                                <p>{activity.description ?? activity.event}</p>
                                                <p className="text-xs text-gray-500">{new Date(activity.created_at).toLocaleString('id-ID')} {activity.actor_name && `· ${activity.actor_name}`}</p>
                                            </div>
                                        ))}
                                    </div>
                                </section>
                                <section className="rounded-lg bg-white p-6 shadow-sm">
                                    <h3 className="text-lg font-medium">Audit Trail Aset</h3>
                                    <div className="mt-4 space-y-3">
                                        {auditLogs.length === 0 && <p className="text-sm text-gray-500">Belum ada audit log.</p>}
                                        {auditLogs.map((log) => (
                                            <div key={log.id} className="text-sm">
                                                <p className="font-medium">{log.event}</p>
                                                <p className="text-xs text-gray-500">{new Date(log.created_at).toLocaleString('id-ID')} {log.actor_name && `· ${log.actor_name}`}</p>
                                            </div>
                                        ))}
                                    </div>
                                </section>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
