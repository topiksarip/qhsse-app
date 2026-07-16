import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ReportTemplate } from '@/types/modules/reporting';
import { PaginationLink } from '@/types/core';
import Pagination from '@/Components/Qhsse/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import EmptyState from '@/Components/UI/EmptyState';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import FilterPanel from '@/Components/UI/FilterPanel';

interface Props {
    templates: { data: ReportTemplate[]; links: PaginationLink[] };
    filters: { search?: string; type?: string; is_active?: string };
    can: { create: boolean; delete: boolean };
}

export default function Index({ templates, filters, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [activeFilter, setActiveFilter] = useState(filters.is_active || '');

    const handleSearch = () => router.get(route('report-templates.index'), { search, type: typeFilter, is_active: activeFilter }, { preserveState: true, preserveScroll: true });
    const handleClearFilters = () => { setSearch(''); setTypeFilter(''); setActiveFilter(''); router.get(route('report-templates.index')); };
    const handleToggleActive = (id: number) => router.post(route('report-templates.toggle-active', id), {}, { preserveScroll: true });

    const typeBadge = (type: string) => {
        const badges: Record<string, string> = {
            incident_summary: 'Insiden', capa_summary: 'CAPA', inspection_summary: 'Inspection', audit_summary: 'Audit',
            training_compliance: 'Training', monthly_qhsse: 'Bulanan QHSSE', annual_qhsse: 'Tahunan QHSSE', custom: 'Custom',
        };
        return badges[type] || type;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Laporan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Report Templates</h2>
                    </div>
                    <PrimaryButton size="sm" href={route('report-templates.create')}>+ Buat Template Custom</PrimaryButton>
                </div>
            }
        >
            <Head title="Report Templates" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, typeFilter, activeFilter].filter(Boolean).length}>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Cari Template</label>
                                <input value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && handleSearch()} placeholder="Cari template..." className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Tipe</label>
                                <select value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
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
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                                <select value={activeFilter} onChange={(e) => setActiveFilter(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <PrimaryButton type="button" onClick={handleSearch}>Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={handleClearFilters}>Clear</SecondaryButton>
                            </div>
                        </div>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nama Template</th>
                                <th className="px-4 py-3">Tipe</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Total Laporan</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {templates.data.map((template) => (
                                <tr key={template.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="px-4 py-3">
                                        <div className="text-sm font-medium text-slate-900 dark:text-slate-100">{template.name}</div>
                                        <div className="text-sm text-gray-500">{template.description}</div>
                                        {template.is_predefined && <span className="mt-1 inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">Pre-defined</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-800 dark:text-slate-200">{typeBadge(template.type)}</td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${template.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'}`}>
                                            {template.is_active ? 'Aktif' : 'Tidak Aktif'}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{template.saved_reports_count || 0} laporan</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('report-templates.show', template.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Lihat</Link>
                                        <Link href={route('saved-reports.create', { template_id: template.id })} className="ml-2 text-green-600 hover:underline dark:text-green-400">Generate</Link>
                                        {!template.is_predefined && <Link href={route('report-templates.edit', template.id)} className="ml-2 text-yellow-600 hover:underline dark:text-yellow-400">Edit</Link>}
                                        <button onClick={() => handleToggleActive(template.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">{template.is_active ? 'Nonaktifkan' : 'Aktifkan'}</button>
                                        {can.delete && !template.is_predefined && (
                                            <DeleteWithConfirm
                                                routeName="report-templates.destroy"
                                                id={template.id}
                                                permission="reporting.templates.delete"
                                                itemLabel={template.name}
                                                redirectTo="report-templates.index"
                                                asLink
                                            >
                                                Hapus
                                            </DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {templates.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-12">
                                        <EmptyState title="Tidak ada template ditemukan" description="Buat template custom untuk laporan QHSSE sesuai kebutuhan organisasi" action={<PrimaryButton href={route('report-templates.create')}>Buat Template Custom</PrimaryButton>} />
                                    </td>
                                </tr>
                            )}
                        </TableBody>
                    </TableWrapper>

                    {templates.data.length > 0 && <div className="mt-6"><Pagination links={templates.links} /></div>}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
