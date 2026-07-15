import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, TrainingProgram, Employee, TrainingRecord } from '@/types';
import { useState } from 'react';
import MatrixCell from '@/Components/Training/MatrixCell';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface MatrixIndexProps extends PageProps {
    programs: TrainingProgram[];
    employees: Employee[];
    matrix: Record<string, Record<string, TrainingRecord | undefined>>;
    sites: Array<{ id: number; name: string }>;
    departments: Array<{ id: number; name: string; site_id: number }>;
    filters: { site_id?: string; department_id?: string; program_category?: string };
}

const categories = [
    { value: 'safety', label: 'Safety' }, { value: 'technical', label: 'Technical' },
    { value: 'compliance', label: 'Compliance' }, { value: 'soft_skill', label: 'Soft Skill' },
    { value: 'environment', label: 'Environment' }, { value: 'security', label: 'Security' },
    { value: 'quality', label: 'Quality' }, { value: 'first_aid', label: 'First Aid' },
];

export default function Index({ auth, programs, employees, matrix, sites, departments, filters }: MatrixIndexProps) {
    const [selectedSite, setSelectedSite] = useState(filters.site_id || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department_id || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.program_category || '');

    const applyFilters = () => {
        router.get(route('training.matrix.index'), {
            site_id: selectedSite || undefined, department_id: selectedDepartment || undefined, program_category: selectedCategory || undefined,
        }, { preserveState: true });
    };

    const validCount = Object.values(matrix).reduce((total, er) => total + Object.values(er).filter(r => r?.status === 'completed' && (!r.expiry_date || new Date(r.expiry_date) > new Date())).length, 0);
    const expiredCount = Object.values(matrix).reduce((total, er) => total + Object.values(er).filter(r => r?.status === 'expired' || (r?.expiry_date && new Date(r.expiry_date) < new Date())).length, 0);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Pelatihan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Training Competency Matrix</h2>
                    </div>
                </div>
            }
        >
            <Head title="Training Matrix" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Site</label>
                                <select value={selectedSite} onChange={(e) => setSelectedSite(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Site</option>
                                    {sites.map((site) => <option key={site.id} value={site.id}>{site.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Department</label>
                                <select value={selectedDepartment} onChange={(e) => setSelectedDepartment(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Department</option>
                                    {departments.filter((d) => !selectedSite || d.site_id === Number(selectedSite)).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Kategori Program</label>
                                <select value={selectedCategory} onChange={(e) => setSelectedCategory(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                    <option value="">Semua Kategori</option>
                                    {categories.map(cat => <option key={cat.value} value={cat.value}>{cat.label}</option>)}
                                </select>
                            </div>
                        </div>
                        <div className="mt-4 flex justify-end">
                            <PrimaryButton type="button" onClick={applyFilters}>Terapkan Filter</PrimaryButton>
                        </div>
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 className="mb-3 text-sm font-medium text-slate-900 dark:text-slate-100">LEGEND (Petunjuk Status)</h3>
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-6">
                            {[['⚪','Belum','bg-gray-100 dark:bg-gray-700/30'],['🔵','Scheduled','bg-blue-100 dark:bg-blue-900/30'],['🟡','In Progress','bg-yellow-100 dark:bg-yellow-900/30'],['🟢','Valid','bg-green-100 dark:bg-green-900/30'],['🔴','Expired','bg-red-100 dark:bg-red-900/30'],['⚪','Cancelled','bg-gray-100 dark:bg-gray-700/30']].map(([icon,label,bg]) => (
                                <div key={label} className="flex items-center gap-2"><div className={`flex h-8 w-8 items-center justify-center rounded-lg text-lg ${bg}`}>{icon}</div><span className="text-sm text-slate-700 dark:text-slate-300">{label}</span></div>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20"><div className="text-sm font-medium text-blue-600 dark:text-blue-400">Total Karyawan</div><div className="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{employees.length}</div></div>
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20"><div className="text-sm font-medium text-emerald-600 dark:text-emerald-400">Total Program</div><div className="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{programs.length}</div></div>
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20"><div className="text-sm font-medium text-green-600 dark:text-green-400">Valid</div><div className="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">{validCount}</div></div>
                        <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20"><div className="text-sm font-medium text-red-600 dark:text-red-400">Expired</div><div className="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">{expiredCount}</div></div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-gray-700">
                            <p className="text-sm text-slate-600 dark:text-slate-400">Matrix {employees.length} karyawan × {programs.length} program</p>
                            <div className="flex gap-2">
                                <SecondaryButton type="button" onClick={() => window.print()}>🖨 Print</SecondaryButton>
                                <button className="rounded-md bg-green-600 px-3 py-1 text-sm font-medium text-white hover:bg-green-700">📊 Export Excel</button>
                            </div>
                        </div>
                        {employees.length === 0 || programs.length === 0 ? (
                            <div className="p-12"><EmptyState title="Data belum tersedia" description="Matrix kompetensi membutuhkan data karyawan dan program pelatihan. Pastikan kedua data sudah tersedia." /></div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full">
                                    <thead className="sticky top-0 z-10 bg-slate-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="sticky left-0 z-20 border-r border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:border-gray-700 dark:bg-gray-900">Karyawan</th>
                                            {programs.map((program) => (
                                                <th key={program.id} title={program.name} className="min-w-[100px] px-2 py-3 text-center text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                                    <div className="flex flex-col items-center gap-1"><span className="font-mono text-xs">{program.code}</span><span className="max-w-[90px] truncate text-[10px] font-normal normal-case">{program.name}</span></div>
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        {employees.map((employee) => (
                                            <tr key={employee.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                                <td className="sticky left-0 z-10 border-r border-slate-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                                                    <div className="text-sm"><div className="font-medium text-slate-900 dark:text-slate-100">{employee.name}</div><div className="text-xs text-gray-500">{employee.employee_no}</div></div>
                                                </td>
                                                {programs.map((program) => {
                                                    const record = matrix[`emp_${employee.id}`]?.[`prog_${program.id}`];
                                                    return <td key={program.id} className="px-2 py-2 text-center"><MatrixCell record={record} employeeId={employee.id} programId={program.id} /></td>;
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 className="mb-3 text-sm font-medium text-slate-900 dark:text-slate-100">QUICK ACTIONS</h3>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('training.programs.index')} className="rounded px-3 py-2 text-sm text-blue-700 hover:bg-blue-100 dark:text-blue-300 dark:hover:bg-blue-900/30">📚 Kelola Program</Link>
                            <Link href={route('training.records.index')} className="rounded px-3 py-2 text-sm text-emerald-700 hover:bg-emerald-100 dark:text-emerald-300 dark:hover:bg-emerald-900/30">📋 Kelola Record</Link>
                            <Link href={route('training.records.create')} className="rounded px-3 py-2 text-sm text-green-700 hover:bg-green-100 dark:text-green-300 dark:hover:bg-green-900/30">➕ Buat Record Baru</Link>
                        </div>
                    </div>

                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <div className="flex items-start gap-3">
                            <div className="text-2xl">💡</div>
                            <div className="flex-1">
                                <h4 className="mb-1 text-sm font-medium text-blue-900 dark:text-blue-100">Tips Menggunakan Matrix</h4>
                                <ul className="space-y-1 text-xs text-blue-700 dark:text-blue-300">
                                    <li>• Klik pada cell berwarna untuk melihat detail record pelatihan</li>
                                    <li>• Cell hijau (🟢) menunjukkan sertifikat masih valid</li>
                                    <li>• Cell merah (🔴) menunjukkan sertifikat sudah expired dan perlu diperbaharui</li>
                                    <li>• Cell abu-abu (⚪) menunjukkan karyawan belum mengikuti pelatihan tersebut</li>
                                    <li>• Gunakan filter untuk mempersempit tampilan matrix berdasarkan site/department/kategori</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
