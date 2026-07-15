import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Qhsse/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { PaginationLink } from '@/types/core';
import EmptyState from '@/Components/UI/EmptyState';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';

interface Campaign {
    id: number; campaign_number: string; title: string; type: string; type_label: string;
    status: string; status_label: string; target_audience_label: string; expires_at: string | null;
    view_count: number; acknowledgments_count: number; author: { name: string }; created_at: string;
}

export default function Index({ campaigns, filters }: { campaigns: { data: Campaign[]; links: PaginationLink[] }; filters: { search?: string; type?: string; status?: string } }) {
    const [search, setSearch] = useState(filters.search || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = () => router.get(route('campaigns.index'), { search, type: typeFilter, status: statusFilter }, { preserveState: true, preserveScroll: true });
    const handleClearFilters = () => { setSearch(''); setTypeFilter(''); setStatusFilter(''); router.get(route('campaigns.index')); };

    const handleDelete = (id: number) => { if (confirm('Hapus campaign ini? Aksi tidak dapat dibatalkan.')) router.delete(route('campaigns.destroy', id), { preserveScroll: true }); };
    const handlePublish = (id: number) => { if (confirm('Publish campaign ini? Campaign akan terlihat oleh target audience.')) router.post(route('campaigns.publish', id), {}, { preserveScroll: true }); };

    const statusBadge = (status: string, label: string) => {
        const colors: Record<string, string> = { draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', published: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', expired: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' };
        return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[status] || colors.draft}`}>{label}</span>;
    };
    const typeBadge = (type: string, label: string) => {
        const colors: Record<string, string> = {
            safety_alert: 'bg-red-50 text-red-700 dark:bg-red-900 dark:text-red-200', policy_update: 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
            training_announcement: 'bg-purple-50 text-purple-700 dark:bg-purple-900 dark:text-purple-200', general_announcement: 'bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        };
        return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[type] || 'bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-200'}`}>{label}</span>;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Komunikasi</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Campaign Management</h2>
                    </div>
                    <PrimaryButton size="sm" href={route('campaigns.create')}>+ Buat Campaign Baru</PrimaryButton>
                </div>
            }
        >
            <Head title="Campaign Management" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cari Campaign</label>
                                <input value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && handleSearch()} placeholder="Cari judul, nomor..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Tipe</label>
                                <select value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Tipe</option>
                                    <option value="safety_alert">Safety Alert</option>
                                    <option value="policy_update">Policy Update</option>
                                    <option value="training_announcement">Training Announcement</option>
                                    <option value="general_announcement">General Announcement</option>
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <PrimaryButton type="button" onClick={handleSearch}>Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={handleClearFilters}>Clear</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Campaign</th>
                                <th className="px-4 py-3">Tipe</th>
                                <th className="px-4 py-3">Target</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Statistik</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {campaigns.data.map((campaign) => (
                                <tr key={campaign.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="px-4 py-3">
                                        <div className="text-sm font-medium text-slate-900 dark:text-slate-100">{campaign.title}</div>
                                        <div className="text-sm text-gray-500">{campaign.campaign_number}</div>
                                        <div className="mt-1 text-xs text-gray-400">Oleh: {campaign.author.name} • {new Date(campaign.created_at).toLocaleDateString('id-ID')}</div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">{typeBadge(campaign.type, campaign.type_label)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{campaign.target_audience_label}</td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        {statusBadge(campaign.status, campaign.status_label)}
                                        {campaign.expires_at && <div className="mt-1 text-xs text-gray-500">Expires: {new Date(campaign.expires_at).toLocaleDateString('id-ID')}</div>}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        <div>👁️ {campaign.view_count} views</div>
                                        <div>✓ {campaign.acknowledgments_count} acks</div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('campaigns.show', campaign.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Lihat</Link>
                                        {campaign.status === 'draft' && (
                                            <>
                                                <Link href={route('campaigns.edit', campaign.id)} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>
                                                <button onClick={() => handlePublish(campaign.id)} className="ml-2 text-green-600 hover:underline dark:text-green-400">📤 Publish</button>
                                            </>
                                        )}
                                        <button onClick={() => handleDelete(campaign.id)} className="ml-2 text-red-600 hover:underline dark:text-red-400">Hapus</button>
                                    </td>
                                </tr>
                            ))}
                            {campaigns.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12">
                                        <EmptyState title="Belum ada campaign" description="Buat campaign untuk komunikasi safety alert, policy update, dan pengumuman QHSSE" action={<PrimaryButton href={route('campaigns.create')}>Buat Campaign Pertama</PrimaryButton>} />
                                    </td>
                                </tr>
                            )}
                        </TableBody>
                    </TableWrapper>

                    {campaigns.data.length > 0 && <div className="mt-6"><Pagination links={campaigns.links} /></div>}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
