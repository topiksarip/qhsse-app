import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SavedReport } from '@/types/modules/reporting';
import { PaginationLink } from '@/types/core';
import Pagination from '@/Components/Qhsse/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import EmptyState from '@/Components/UI/EmptyState';

interface Props {
    reports: {
        data: SavedReport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: PaginationLink[];
    };
    filters: {
        search?: string;
        status?: string;
        template_id?: string;
    };
}

export default function Index({ reports, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = () => {
        router.get(route('saved-reports.index'), {
            search,
            status: statusFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearFilters = () => {
        setSearch('');
        setStatusFilter('');
        router.get(route('saved-reports.index'));
    };

    const handleRegenerate = (reportId: number) => {
        if (confirm('Regenerate laporan ini? Data akan di-update dengan data terbaru.')) {
            router.post(route('saved-reports.regenerate', reportId), {}, {
                preserveScroll: true,
            });
        }
    };

    const handleDelete = (reportId: number) => {
        if (confirm('Hapus laporan ini? Aksi ini tidak dapat dibatalkan.')) {
            router.delete(route('saved-reports.destroy', reportId), {
                preserveScroll: true,
            });
        }
    };

    const getStatusBadge = (status: string) => {
        const badges: Record<string, { label: string; color: string }> = {
            pending: { label: '⏳ Pending', color: 'bg-yellow-100 text-yellow-800' },
            processing: { label: '🔄 Processing', color: 'bg-blue-100 text-blue-800' },
            completed: { label: '✓ Selesai', color: 'bg-green-100 text-green-800' },
            failed: { label: '✗ Gagal', color: 'bg-red-100 text-red-800' },
        };

        const badge = badges[status] || { label: status, color: 'bg-gray-100 text-gray-800' };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.color}`}>
                {badge.label}
            </span>
        );
    };

    const formatFileSize = (bytes?: number | null) => {
        if (!bytes) return 'N/A';
        const mb = bytes / (1024 * 1024);
        return `${mb.toFixed(2)} MB`;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Saved Reports" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Saved Reports</h1>
                            <p className="text-sm text-gray-600 mt-1">Kelola laporan QHSSE yang telah di-generate</p>
                        </div>
                        <Link href={route('saved-reports.create')}>
                            <PrimaryButton>+ Generate Report Baru</PrimaryButton>
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6 p-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="search" value="Cari Laporan" />
                                <TextInput
                                    id="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                    placeholder="Cari laporan..."
                                    className="mt-1 block w-full"
                                />
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
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Selesai</option>
                                    <option value="failed">Gagal</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <PrimaryButton onClick={handleSearch}>Filter</PrimaryButton>
                                <SecondaryButton onClick={handleClearFilters}>Clear</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    {/* Reports List */}
                    <div className="space-y-4">
                        {reports.data.map((report) => (
                            <div key={report.id} className="bg-white shadow rounded-lg p-6 hover:shadow-lg transition-shadow">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            <span className="text-2xl">📊</span>
                                            <div>
                                                <h3 className="text-lg font-semibold text-gray-900">{report.name}</h3>
                                                <p className="text-sm text-gray-600">{report.template?.name}</p>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 mb-3">
                                            <div>
                                                <span className="font-medium">Periode:</span> {report.date_from} s/d {report.date_to}
                                            </div>
                                            <div>
                                                <span className="font-medium">Format:</span> {report.format?.toUpperCase()}
                                            </div>
                                            <div>
                                                <span className="font-medium">Ukuran:</span> {formatFileSize(report.file_size)}
                                            </div>
                                            <div>
                                                <span className="font-medium">Dibuat:</span> {new Date(report.created_at).toLocaleDateString('id-ID')}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {getStatusBadge(report.status)}
                                            {report.status === 'failed' && report.error_message && (
                                                <span className="text-xs text-red-600">{report.error_message}</span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex gap-2 ml-4">
                                        <Link href={route('saved-reports.show', report.id)}>
                                            <SecondaryButton>Lihat</SecondaryButton>
                                        </Link>
                                        {report.status === 'completed' && (
                                            <>
                                                <Link href={route('saved-reports.download', report.id)}>
                                                    <PrimaryButton>Download</PrimaryButton>
                                                </Link>
                                                <SecondaryButton
                                                    onClick={() => handleRegenerate(report.id)}
                                                >
                                                    ↻ Regenerate
                                                </SecondaryButton>
                                            </>
                                        )}
                                        {report.status === 'failed' && (
                                            <PrimaryButton
                                                onClick={() => handleRegenerate(report.id)}
                                            >
                                                ↻ Coba Lagi
                                            </PrimaryButton>
                                        )}
                                        {(report.status === 'pending' || report.status === 'processing') && (
                                            <span className="px-3 py-2 text-sm text-gray-500">Tunggu...</span>
                                        )}
                                        <button
                                            onClick={() => handleDelete(report.id)}
                                            className="px-3 py-2 text-sm font-medium text-red-600 hover:text-red-900"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Empty State */}
                    {reports.data.length === 0 && (
                        <div className="py-12">
                            <EmptyState
                                title="Belum ada laporan yang di-generate"
                                description="Generate laporan QHSSE dari template untuk dokumentasi dan analisis"
                                action={
                                    <Link href={route('saved-reports.create')}>
                                        <PrimaryButton>Generate Report Baru</PrimaryButton>
                                    </Link>
                                }
                            />
                        </div>
                    )}

                    {/* Pagination */}
                    {reports.data.length > 0 && (
                        <div className="mt-6">
                            <Pagination links={reports.links} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
