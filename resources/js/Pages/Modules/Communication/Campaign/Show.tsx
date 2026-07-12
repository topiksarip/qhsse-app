import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface Campaign {
    id: number;
    campaign_number: string;
    title: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    target_audience: string;
    target_audience_label: string;
    description?: string;
    content?: string;
    published_at: string | null;
    expires_at: string | null;
    view_count: number;
    acknowledgments_count: number;
    author: { id: number; name: string };
    created_at: string;
    updated_at: string;
    attachments?: { id: number; file_name: string; file_path: string; file_size: number }[];
}

interface Acknowledgment {
    id: number;
    user: { id: number; name: string };
    acknowledged_at: string;
    comments?: string;
}

interface Props {
    campaign: Campaign;
    acknowledgments: {
        data: Acknowledgment[];
        total: number;
    };
    canEdit: boolean;
    userHasAcknowledged: boolean;
}

export default function Show({ campaign, acknowledgments, canEdit, userHasAcknowledged }: Props) {
    const handleAcknowledge = () => {
        router.post(route('campaigns.acknowledge', campaign.id), {}, {
            preserveScroll: true,
        });
    };

    const handlePublish = () => {
        if (confirm('Publish campaign ini? Campaign akan terlihat oleh target audience.')) {
            router.post(route('campaigns.publish', campaign.id), {}, {
                preserveScroll: true,
            });
        }
    };

    const handleDelete = () => {
        if (confirm('Hapus campaign ini? Aksi tidak dapat dibatalkan.')) {
            router.delete(route('campaigns.destroy', campaign.id));
        }
    };

    const getStatusBadge = (status: string, label: string) => {
        const colors: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800',
            published: 'bg-green-100 text-green-800',
            expired: 'bg-red-100 text-red-800',
        };
        const color = colors[status] || 'bg-gray-100 text-gray-800';
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${color}`}>{label}</span>;
    };

    const getTypeBadge = (type: string, label: string) => {
        const colors: Record<string, string> = {
            safety_alert: 'bg-red-50 text-red-700',
            policy_update: 'bg-blue-50 text-blue-700',
            training_announcement: 'bg-purple-50 text-purple-700',
            general_announcement: 'bg-gray-50 text-gray-700',
        };
        const color = colors[type] || 'bg-gray-50 text-gray-700';
        return <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${color}`}>{label}</span>;
    };

    const formatFileSize = (bytes: number) => {
        const mb = bytes / (1024 * 1024);
        return `${mb.toFixed(2)} MB`;
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Campaign: ${campaign.title}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-start mb-6">
                        <div className="flex-1">
                            <div className="flex items-center gap-3 mb-2">
                                <Link href={route('campaigns.index')}>
                                    <SecondaryButton>← Kembali</SecondaryButton>
                                </Link>
                            </div>
                            <h1 className="text-2xl font-bold text-gray-900">{campaign.title}</h1>
                            <div className="flex items-center gap-3 mt-2">
                                <span className="text-sm text-gray-500">{campaign.campaign_number}</span>
                                {getStatusBadge(campaign.status, campaign.status_label)}
                                {getTypeBadge(campaign.type, campaign.type_label)}
                            </div>
                        </div>
                        <div className="flex gap-2">
                            {canEdit && campaign.status === 'draft' && (
                                <>
                                    <Link href={route('campaigns.edit', campaign.id)}>
                                        <SecondaryButton>Edit</SecondaryButton>
                                    </Link>
                                    <PrimaryButton onClick={handlePublish}>📤 Publish</PrimaryButton>
                                </>
                            )}
                            {!userHasAcknowledged && campaign.status === 'published' && (
                                <PrimaryButton onClick={handleAcknowledge}>✓ Acknowledge</PrimaryButton>
                            )}
                            {canEdit && (
                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-900"
                                >
                                    Hapus
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Campaign Details */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Detail Campaign</h2>
                                
                                <div className="space-y-4">
                                    {campaign.description && (
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-700 mb-1">Deskripsi</h3>
                                            <p className="text-sm text-gray-600">{campaign.description}</p>
                                        </div>
                                    )}

                                    {campaign.content && (
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-700 mb-1">Konten</h3>
                                            <div className="text-sm text-gray-600 prose prose-sm max-w-none">
                                                <div dangerouslySetInnerHTML={{ __html: campaign.content }} />
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                        <div>
                                            <span className="text-sm font-medium text-gray-700">Target Audience:</span>
                                            <p className="text-sm text-gray-600 mt-1">{campaign.target_audience_label}</p>
                                        </div>
                                        {campaign.expires_at && (
                                            <div>
                                                <span className="text-sm font-medium text-gray-700">Expires:</span>
                                                <p className="text-sm text-gray-600 mt-1">
                                                    {new Date(campaign.expires_at).toLocaleDateString('id-ID')}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Attachments */}
                            {campaign.attachments && campaign.attachments.length > 0 && (
                                <div className="bg-white shadow rounded-lg p-6">
                                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Attachments</h2>
                                    <div className="space-y-2">
                                        {campaign.attachments.map((file) => (
                                            <div key={file.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                                                <div className="flex items-center gap-3">
                                                    <span className="text-2xl">📎</span>
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">{file.file_name}</p>
                                                        <p className="text-xs text-gray-500">{formatFileSize(file.file_size)}</p>
                                                    </div>
                                                </div>
                                                <a
                                                    href={`/storage/${file.file_path}`}
                                                    download
                                                    className="text-sm text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Download
                                                </a>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Acknowledgments */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Acknowledgments ({acknowledgments.total})
                                </h2>
                                
                                {acknowledgments.data.length > 0 ? (
                                    <div className="space-y-3">
                                        {acknowledgments.data.map((ack) => (
                                            <div key={ack.id} className="border-l-4 border-green-400 pl-4 py-2">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">{ack.user.name}</p>
                                                        <p className="text-xs text-gray-500">
                                                            {new Date(ack.acknowledged_at).toLocaleString('id-ID')}
                                                        </p>
                                                    </div>
                                                    <span className="text-green-600 text-sm">✓ Acknowledged</span>
                                                </div>
                                                {ack.comments && (
                                                    <p className="text-sm text-gray-600 mt-2">{ack.comments}</p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">Belum ada acknowledgment</p>
                                )}
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Statistics */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Statistik</h2>
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600">👁️ Views</span>
                                        <span className="text-lg font-semibold text-gray-900">{campaign.view_count}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600">✓ Acknowledgments</span>
                                        <span className="text-lg font-semibold text-gray-900">{campaign.acknowledgments_count}</span>
                                    </div>
                                    {campaign.acknowledgments_count > 0 && campaign.view_count > 0 && (
                                        <div className="pt-4 border-t">
                                            <span className="text-sm text-gray-600">Acknowledgment Rate</span>
                                            <div className="mt-2">
                                                <div className="w-full bg-gray-200 rounded-full h-2">
                                                    <div
                                                        className="bg-green-600 h-2 rounded-full"
                                                        style={{ width: `${(campaign.acknowledgments_count / campaign.view_count) * 100}%` }}
                                                    ></div>
                                                </div>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    {Math.round((campaign.acknowledgments_count / campaign.view_count) * 100)}%
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Info */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Informasi</h2>
                                <div className="space-y-3 text-sm">
                                    <div>
                                        <span className="font-medium text-gray-700">Dibuat oleh:</span>
                                        <p className="text-gray-600 mt-1">{campaign.author.name}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium text-gray-700">Dibuat pada:</span>
                                        <p className="text-gray-600 mt-1">
                                            {new Date(campaign.created_at).toLocaleString('id-ID')}
                                        </p>
                                    </div>
                                    {campaign.published_at && (
                                        <div>
                                            <span className="font-medium text-gray-700">Published pada:</span>
                                            <p className="text-gray-600 mt-1">
                                                {new Date(campaign.published_at).toLocaleString('id-ID')}
                                            </p>
                                        </div>
                                    )}
                                    <div>
                                        <span className="font-medium text-gray-700">Terakhir diupdate:</span>
                                        <p className="text-gray-600 mt-1">
                                            {new Date(campaign.updated_at).toLocaleString('id-ID')}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
