import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import { PaginationLink } from '@/types/core';
import Pagination from '@/Components/Qhsse/Pagination';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';

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
    published_at: string | null;
    expires_at: string | null;
    view_count: number;
    acknowledgments_count: number;
    author: { name: string };
    created_at: string;
}

interface PaginatedCampaigns {
    data: Campaign[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Props {
    campaigns: PaginatedCampaigns;
    filters: {
        search?: string;
        type?: string;
        status?: string;
    };
}

export default function Index({ campaigns, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('campaigns.index'), {
            search,
            type: typeFilter,
            status: statusFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearFilters = () => {
        setSearch('');
        setTypeFilter('');
        setStatusFilter('');
        router.get(route('campaigns.index'));
    };

    const handleDelete = (campaignId: number) => {
        if (confirm('Hapus campaign ini? Aksi tidak dapat dibatalkan.')) {
            router.delete(route('campaigns.destroy', campaignId), {
                preserveScroll: true,
            });
        }
    };

    const handlePublish = (campaignId: number) => {
        if (confirm('Publish campaign ini? Campaign akan terlihat oleh target audience.')) {
            router.post(route('campaigns.publish', campaignId), {}, {
                preserveScroll: true,
            });
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

    return (
        <AuthenticatedLayout>
            <Head title="Campaign Management" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Campaign Management</h1>
                            <p className="text-sm text-gray-600 mt-1">Kelola kampanye komunikasi dan awareness QHSSE</p>
                        </div>
                        <Link href={route('campaigns.create')}>
                            <PrimaryButton>+ Buat Campaign Baru</PrimaryButton>
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6 p-6">
                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="search" value="Cari Campaign" />
                                <TextInput
                                    id="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                    placeholder="Cari judul, nomor..."
                                    className="mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <InputLabel htmlFor="type" value="Tipe" />
                                <select
                                    id="type"
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="">Semua Tipe</option>
                                    <option value="safety_alert">Safety Alert</option>
                                    <option value="policy_update">Policy Update</option>
                                    <option value="training_announcement">Training Announcement</option>
                                    <option value="general_announcement">General Announcement</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel htmlFor="status" value="Status" />
                                <select
                                    id="status"
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <PrimaryButton onClick={handleSearch}>Filter</PrimaryButton>
                                <SecondaryButton onClick={handleClearFilters}>Clear</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    {/* Campaign List */}
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statistik</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {campaigns.data.map((campaign) => (
                                    <tr key={campaign.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="text-sm font-medium text-gray-900">{campaign.title}</div>
                                            <div className="text-sm text-gray-500">{campaign.campaign_number}</div>
                                            <div className="text-xs text-gray-400 mt-1">
                                                Oleh: {campaign.author.name} • {new Date(campaign.created_at).toLocaleDateString('id-ID')}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getTypeBadge(campaign.type, campaign.type_label)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {campaign.target_audience_label}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(campaign.status, campaign.status_label)}
                                            {campaign.expires_at && (
                                                <div className="text-xs text-gray-500 mt-1">
                                                    Expires: {new Date(campaign.expires_at).toLocaleDateString('id-ID')}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>👁️ {campaign.view_count} views</div>
                                            <div>✓ {campaign.acknowledgments_count} acks</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div className="flex justify-end gap-2">
                                                <Link href={route('campaigns.show', campaign.id)}>
                                                    <SecondaryButton>Lihat</SecondaryButton>
                                                </Link>
                                                {campaign.status === 'draft' && (
                                                    <>
                                                        <Link href={route('campaigns.edit', campaign.id)}>
                                                            <SecondaryButton>Edit</SecondaryButton>
                                                        </Link>
                                                        <SecondaryButton onClick={() => handlePublish(campaign.id)}>
                                                            📤 Publish
                                                        </SecondaryButton>
                                                    </>
                                                )}
                                                <button
                                                    onClick={() => handleDelete(campaign.id)}
                                                    className="px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {campaigns.data.length === 0 && (
                            <div className="py-12">
                                <EmptyState
                                    title="Belum ada campaign"
                                    description="Buat campaign untuk komunikasi safety alert, policy update, dan pengumuman QHSSE"
                                    action={
                                        <Link href={route('campaigns.create')}>
                                            <PrimaryButton>Buat Campaign Pertama</PrimaryButton>
                                        </Link>
                                    }
                                />
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {campaigns.data.length > 0 && (
                        <div className="mt-6">
                            <Pagination links={campaigns.links} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
