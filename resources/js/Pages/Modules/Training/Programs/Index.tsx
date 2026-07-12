import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, TrainingProgram } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';

interface ProgramsIndexProps extends PageProps {
    programs: PaginatedData<TrainingProgram>;
    filters: {
        search?: string;
        category?: string;
        is_active?: string;
    };
    can: {
        create: boolean;
        update: boolean;
    };
}

export default function Index({ auth, programs, filters, can }: ProgramsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');
    const [isActive, setIsActive] = useState(filters.is_active || '');

    const handleFilter = () => {
        router.get(route('training.programs.index'), {
            search,
            category: category || undefined,
            is_active: isActive || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setCategory('');
        setIsActive('');
        router.get(route('training.programs.index'));
    };

    const categories = [
        { value: 'safety', label: 'Safety' },
        { value: 'technical', label: 'Technical' },
        { value: 'compliance', label: 'Compliance' },
        { value: 'soft_skill', label: 'Soft Skill' },
        { value: 'environment', label: 'Environment' },
        { value: 'security', label: 'Security' },
        { value: 'quality', label: 'Quality' },
        { value: 'first_aid', label: 'First Aid' },
    ];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            Program Pelatihan
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola program pelatihan dan sertifikasi QHSSE
                        </p>
                    </div>
                    {can.create && (
                        <Link
                            href={route('training.programs.create')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            + Buat Program
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Program Pelatihan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Filter Bar */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div className="md:col-span-2">
                                <input
                                    type="text"
                                    placeholder="🔍 Cari kode, nama program..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <select
                                    value={category}
                                    onChange={(e) => setCategory(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Kategori: Semua</option>
                                    {categories.map(cat => (
                                        <option key={cat.value} value={cat.value}>{cat.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <select
                                    value={isActive}
                                    onChange={(e) => setIsActive(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Status: Semua</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Non-aktif</option>
                                </select>
                            </div>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <button
                                onClick={handleFilter}
                                className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                            >
                                Filter
                            </button>
                            <button
                                onClick={handleReset}
                                className="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    {/* Programs Table */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Menampilkan {programs.from || 0}–{programs.to || 0} dari {programs.total} program
                            </p>
                        </div>

                        {programs.data.length === 0 ? (
                            <div className="p-12">
                                <EmptyState
                                    title="Belum ada program pelatihan"
                                    description="Kelola program pelatihan, sertifikasi, dan kompetensi QHSSE untuk karyawan"
                                    action={
                                        can.create ? (
                                            <Link
                                                href={route('training.programs.create')}
                                                className="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                            >
                                                + Buat Program Pertama
                                            </Link>
                                        ) : undefined
                                    }
                                />
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Kode
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Nama Program
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Kategori
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Durasi
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Sertifikasi
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Aktif
                                            </th>
                                            <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {programs.data.map((program) => (
                                            <tr key={program.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className="font-mono text-sm text-gray-900 dark:text-gray-100">
                                                        {program.code}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={route('training.programs.show', program.id)}
                                                        className="block max-w-xs truncate text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                    >
                                                        {program.name}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                        {program.category}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                                                    {program.duration_hours} jam
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center">
                                                    {program.is_certification ? (
                                                        <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                            Ya
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-400 dark:text-gray-500">Tidak</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center">
                                                    {program.is_active ? '✅' : '❌'}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-center text-sm">
                                                    <div className="flex items-center justify-center gap-2">
                                                        <Link
                                                            href={route('training.programs.show', program.id)}
                                                            className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                        >
                                                            Detail
                                                        </Link>
                                                        {can.update && (
                                                            <Link
                                                                href={route('training.programs.edit', program.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                            >
                                                                ✏ Edit
                                                            </Link>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Pagination */}
                        {programs.last_page > 1 && (
                            <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-center gap-2">
                                {programs.current_page > 1 && (
                                    <Link
                                        href={route('training.programs.index', { ...filters, page: programs.current_page - 1 })}
                                        className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        ‹ Sebelumnya
                                    </Link>
                                )}
                                {Array.from({ length: programs.last_page }, (_, i) => i + 1).map(page => (
                                    <Link
                                        key={page}
                                        href={route('training.programs.index', { ...filters, page })}
                                        className={`px-3 py-1 text-sm border rounded ${
                                            page === programs.current_page
                                                ? 'bg-indigo-600 text-white border-indigo-600'
                                                : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        {page}
                                    </Link>
                                ))}
                                {programs.current_page < programs.last_page && (
                                    <Link
                                        href={route('training.programs.index', { ...filters, page: programs.current_page + 1 })}
                                        className="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Berikutnya ›
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
