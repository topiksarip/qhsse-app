import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, TrainingProgram } from '@/types';
import { useState } from 'react';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import FilterPanel from '@/Components/UI/FilterPanel';

interface ProgramsIndexProps extends PageProps {
    programs: PaginatedData<TrainingProgram>;
    filters: { search?: string; category?: string; is_active?: string };
    can: { create: boolean; update: boolean; delete: boolean };
}

const categories = [
    { value: 'safety', label: 'Safety' }, { value: 'technical', label: 'Technical' },
    { value: 'compliance', label: 'Compliance' }, { value: 'soft_skill', label: 'Soft Skill' },
    { value: 'environment', label: 'Environment' }, { value: 'security', label: 'Security' },
    { value: 'quality', label: 'Quality' }, { value: 'first_aid', label: 'First Aid' },
];

export default function Index({ auth, programs, filters, can }: ProgramsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || '');
    const [isActive, setIsActive] = useState(filters.is_active || '');

    const handleFilter = () => {
        router.get(route('training.programs.index'), { search, category: category || undefined, is_active: isActive || undefined }, { preserveState: true, preserveScroll: true });
    };
    const handleReset = () => {
        setSearch(''); setCategory(''); setIsActive('');
        router.get(route('training.programs.index'));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Pelatihan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Program Pelatihan</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.create && <PrimaryButton size="sm" href={route('training.programs.create')}>+ Buat Program</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Program Pelatihan" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, category, isActive].filter(Boolean).length}>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input type="text" placeholder="🔍 Cari kode, nama program..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyPress={(e) => e.key === 'Enter' && handleFilter()} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                            <select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Kategori: Semua</option>
                                {categories.map(cat => <option key={cat.value} value={cat.value}>{cat.label}</option>)}
                            </select>
                            <select value={isActive} onChange={(e) => setIsActive(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Status: Semua</option>
                                <option value="1">Aktif</option>
                                <option value="0">Non-aktif</option>
                            </select>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="button" onClick={handleFilter}>Filter</PrimaryButton>
                            <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                        </div>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Kode</th>
                                <th className="px-4 py-3">Nama Program</th>
                                <th className="px-4 py-3">Kategori</th>
                                <th className="px-4 py-3 text-center">Durasi</th>
                                <th className="px-4 py-3 text-center">Sertifikasi</th>
                                <th className="px-4 py-3 text-center">Aktif</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {programs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada program pelatihan"
                                            description="Kelola program pelatihan, sertifikasi, dan kompetensi QHSSE untuk karyawan"
                                            action={can.create ? <PrimaryButton href={route('training.programs.create')}>+ Buat Program Pertama</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : programs.data.map((program) => (
                                <tr key={program.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-sm text-slate-900 dark:text-slate-100">{program.code}</td>
                                    <td className="px-4 py-3">
                                        <Link href={route('training.programs.show', program.id)} className="block max-w-xs truncate text-sm font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{program.name}</Link>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        <span className="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{program.category}</span>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-900 dark:text-slate-100">{program.duration_hours} jam</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center">
                                        {program.is_certification ? <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200">Ya</span> : <span className="text-gray-400 dark:text-gray-500">Tidak</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center">{program.is_active ? '✅' : '❌'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <Link href={route('training.programs.show', program.id)} className="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">Detail</Link>
                                        {can.update && <Link href={route('training.programs.edit', program.id)} className="ml-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">✏ Edit</Link>}
                                        {can.delete && (
                                            <DeleteWithConfirm
                                                routeName="training.programs.destroy"
                                                id={program.id}
                                                permission="training.programs.delete"
                                                itemLabel={program.name}
                                                redirectTo="training.programs.index"
                                                asLink
                                            >
                                                🗑 Hapus
                                            </DeleteWithConfirm>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    {programs.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {programs.current_page > 1 && <Link href={route('training.programs.index', { ...filters, page: programs.current_page - 1 })} className="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-800">‹ Sebelumnya</Link>}
                            {Array.from({ length: programs.last_page }, (_, i) => i + 1).map(page => (
                                <Link key={page} href={route('training.programs.index', { ...filters, page })} className={`px-3 py-1 text-sm border rounded ${page === programs.current_page ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-800'}`}>{page}</Link>
                            ))}
                            {programs.current_page < programs.last_page && <Link href={route('training.programs.index', { ...filters, page: programs.current_page + 1 })} className="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-800">Berikutnya ›</Link>}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
