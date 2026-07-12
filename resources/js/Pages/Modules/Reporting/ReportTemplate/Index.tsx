import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ReportTemplate } from '@/types/modules/reporting';
import { PaginationLink } from '@/types/core';
import Pagination from '@/Components/Qhsse/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import EmptyState from '@/Components/UI/EmptyState';

interface Props {
    templates: {
        data: ReportTemplate[];
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
        type?: string;
        is_active?: string;
        is_predefined?: string;
    };
}

export default function Index({ templates, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [activeFilter, setActiveFilter] = useState(filters.is_active || '');

    const handleSearch = () => {
        router.get(route('report-templates.index'), {
            search,
            type: typeFilter,
            is_active: activeFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearFilters = () => {
        setSearch('');
        setTypeFilter('');
        setActiveFilter('');
        router.get(route('report-templates.index'));
    };

    const handleToggleActive = (templateId: number) => {
        router.post(route('report-templates.toggle-active', templateId), {}, {
            preserveScroll: true,
        });
    };

    const getTypeBadge = (type: string) => {
        const badges: Record<string, string> = {
            incident_summary: 'Insiden',
            capa_summary: 'CAPA',
            inspection_summary: 'Inspection',
            audit_summary: 'Audit',
            training_compliance: 'Training',
            monthly_qhsse: 'Bulanan QHSSE',
            annual_qhsse: 'Tahunan QHSSE',
            custom: 'Custom',
        };
        return badges[type] || type;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Report Templates" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Report Templates</h1>
                            <p className="text-sm text-gray-600 mt-1">Kelola template laporan QHSSE</p>
                        </div>
                        <Link href={route('report-templates.create')}>
                            <PrimaryButton>+ Buat Template Custom</PrimaryButton>
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="bg-white shadow rounded-lg mb-6 p-6">
                        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="search" value="Cari Template" />
                                <TextInput
                                    id="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                    placeholder="Cari template..."
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
                                    <option value="incident_summary">Insiden</option>
                                    <option value="capa_summary">CAPA</option>
                                    <option value="inspection_summary">Inspection</option>
                                    <option value="audit_summary">Audit</option>
                                    <option value="training_compliance">Training</option>
                                    <option value="monthly_qhsse">Bulanan QHSSE</option>
                                    <option value="annual_qhsse">Tahunan QHSSE</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel htmlFor="status" value="Status" />
                                <select
                                    id="status"
                                    value={activeFilter}
                                    onChange={(e) => setActiveFilter(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <PrimaryButton onClick={handleSearch}>Filter</PrimaryButton>
                                <SecondaryButton onClick={handleClearFilters}>Clear</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    {/* Templates Table */}
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Template</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Laporan</th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {templates.data.map((template) => (
                                    <tr key={template.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4">
                                            <div className="text-sm font-medium text-gray-900">{template.name}</div>
                                            <div className="text-sm text-gray-500">{template.description}</div>
                                            {template.is_predefined && (
                                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                                    Pre-defined
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="text-sm text-gray-900">{getTypeBadge(template.type)}</span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                            }`}>
                                                {template.is_active ? 'Aktif' : 'Tidak Aktif'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {template.saved_reports_count || 0} laporan
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link href={route('report-templates.show', template.id)} className="text-blue-600 hover:text-blue-900 mr-3">
                                                Lihat
                                            </Link>
                                            <Link href={route('saved-reports.create', { template_id: template.id })} className="text-green-600 hover:text-green-900 mr-3">
                                                Generate
                                            </Link>
                                            {!template.is_predefined && (
                                                <Link href={route('report-templates.edit', template.id)} className="text-yellow-600 hover:text-yellow-900 mr-3">
                                                    Edit
                                                </Link>
                                            )}
                                            <button
                                                onClick={() => handleToggleActive(template.id)}
                                                className="text-gray-600 hover:text-gray-900"
                                            >
                                                {template.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {templates.data.length === 0 && (
                            <div className="py-12">
                                <EmptyState
                                    title="Tidak ada template ditemukan"
                                    description="Buat template custom untuk laporan QHSSE sesuai kebutuhan organisasi"
                                    action={
                                        <Link href={route('report-templates.create')}>
                                            <PrimaryButton>Buat Template Custom</PrimaryButton>
                                        </Link>
                                    }
                                />
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {templates.data.length > 0 && (
                        <div className="mt-6">
                            <Pagination links={templates.links} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
