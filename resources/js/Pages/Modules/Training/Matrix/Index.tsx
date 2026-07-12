import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, TrainingProgram, Employee, TrainingRecord } from '@/types';
import { useState } from 'react';
import MatrixCell from '@/Components/Training/MatrixCell';
import EmptyState from '@/Components/UI/EmptyState';

interface MatrixIndexProps extends PageProps {
    programs: TrainingProgram[];
    employees: Employee[];
    matrix: Record<string, Record<string, TrainingRecord | undefined>>;
    filters: {
        site_id?: string;
        department_id?: string;
        program_category?: string;
    };
}

export default function Index({ auth, programs, employees, matrix, filters }: MatrixIndexProps) {
    const [selectedSite, setSelectedSite] = useState(filters.site_id || '');
    const [selectedDepartment, setSelectedDepartment] = useState(filters.department_id || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.program_category || '');

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
                <div>
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        Training Competency Matrix
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Matriks kompetensi pelatihan karyawan per program
                    </p>
                </div>
            }
        >
            <Head title="Training Matrix" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Filter Bar */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Site
                                </label>
                                <select
                                    value={selectedSite}
                                    onChange={(e) => setSelectedSite(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Site</option>
                                    {/* Sites will be loaded from backend */}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Department
                                </label>
                                <select
                                    value={selectedDepartment}
                                    onChange={(e) => setSelectedDepartment(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Department</option>
                                    {/* Departments will be loaded from backend */}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Kategori Program
                                </label>
                                <select
                                    value={selectedCategory}
                                    onChange={(e) => setSelectedCategory(e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Kategori</option>
                                    {categories.map(cat => (
                                        <option key={cat.value} value={cat.value}>{cat.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Legend */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                            LEGEND (Petunjuk Status)
                        </h3>
                        <div className="grid grid-cols-2 md:grid-cols-6 gap-3">
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700/30 flex items-center justify-center text-lg">
                                    ⚪
                                </div>
                                <span className="text-sm text-gray-700 dark:text-gray-300">Belum</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-lg">
                                    🔵
                                </div>
                                <span className="text-sm text-gray-700 dark:text-gray-300">Scheduled</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center text-lg">
                                    🟡
                                </div>
                                <span className="text-sm text-gray-700 dark:text-gray-300">In Progress</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-lg">
                                    🟢
                                </div>
                                <span className="text-sm text-gray-700 dark:text-gray-300">Valid</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-lg">
                                    🔴
                                </div>
                                <span className="text-sm text-gray-700 dark:text-gray-300">Expired</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700/30 flex items-center justify-center text-lg">
                                    ⚪
                                </div>
                                <span className="text-sm text-gray-700 dark:text-gray-300">Cancelled</span>
                            </div>
                        </div>
                    </div>

                    {/* Summary Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <div className="text-sm text-blue-600 dark:text-blue-400 font-medium">Total Karyawan</div>
                            <div className="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">
                                {employees.length}
                            </div>
                        </div>
                        <div className="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                            <div className="text-sm text-indigo-600 dark:text-indigo-400 font-medium">Total Program</div>
                            <div className="text-2xl font-bold text-indigo-700 dark:text-indigo-300 mt-1">
                                {programs.length}
                            </div>
                        </div>
                        <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                            <div className="text-sm text-green-600 dark:text-green-400 font-medium">Valid</div>
                            <div className="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">
                                {Object.values(matrix).reduce((total, employeeRecords) => {
                                    return total + Object.values(employeeRecords).filter(
                                        r => r?.status === 'completed' && (!r.expiry_date || new Date(r.expiry_date) > new Date())
                                    ).length;
                                }, 0)}
                            </div>
                        </div>
                        <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                            <div className="text-sm text-red-600 dark:text-red-400 font-medium">Expired</div>
                            <div className="text-2xl font-bold text-red-700 dark:text-red-300 mt-1">
                                {Object.values(matrix).reduce((total, employeeRecords) => {
                                    return total + Object.values(employeeRecords).filter(
                                        r => r?.status === 'expired' || (r?.expiry_date && new Date(r.expiry_date) < new Date())
                                    ).length;
                                }, 0)}
                            </div>
                        </div>
                    </div>

                    {/* Matrix Table */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Matrix {employees.length} karyawan × {programs.length} program
                                </p>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => window.print()}
                                        className="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                                    >
                                        🖨 Print
                                    </button>
                                    <button
                                        className="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                                    >
                                        📊 Export Excel
                                    </button>
                                </div>
                            </div>
                        </div>

                        {employees.length === 0 || programs.length === 0 ? (
                            <div className="p-12">
                                <EmptyState
                                    title="Data belum tersedia"
                                    description="Matrix kompetensi membutuhkan data karyawan dan program pelatihan. Pastikan kedua data sudah tersedia."
                                />
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10">
                                        <tr>
                                            <th className="sticky left-0 z-20 px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700">
                                                Karyawan
                                            </th>
                                            {programs.map((program) => (
                                                <th
                                                    key={program.id}
                                                    className="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[100px]"
                                                    title={program.name}
                                                >
                                                    <div className="flex flex-col items-center gap-1">
                                                        <span className="font-mono text-xs">{program.code}</span>
                                                        <span className="text-[10px] font-normal normal-case truncate max-w-[90px]">
                                                            {program.name}
                                                        </span>
                                                    </div>
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {employees.map((employee) => (
                                            <tr key={employee.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                {/* Employee Name (Sticky Column) */}
                                                <td className="sticky left-0 z-10 px-4 py-3 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
                                                    <div className="text-sm">
                                                        <div className="font-medium text-gray-900 dark:text-gray-100">
                                                            {employee.name}
                                                        </div>
                                                        <div className="text-gray-500 dark:text-gray-400 text-xs">
                                                            {employee.employee_number}
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Matrix Cells for Each Program */}
                                                {programs.map((program) => {
                                                    const employeeKey = `emp_${employee.id}`;
                                                    const programKey = `prog_${program.id}`;
                                                    const record = matrix[employeeKey]?.[programKey];

                                                    return (
                                                        <td key={program.id} className="px-2 py-2 text-center">
                                                            <MatrixCell
                                                                record={record}
                                                                employeeId={employee.id}
                                                                programId={program.id}
                                                            />
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                        <h3 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                            QUICK ACTIONS
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href={route('training.programs.index')}
                                className="px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm rounded hover:bg-blue-200 dark:hover:bg-blue-800/50"
                            >
                                📚 Kelola Program
                            </Link>
                            <Link
                                href={route('training.records.index')}
                                className="px-3 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm rounded hover:bg-indigo-200 dark:hover:bg-indigo-800/50"
                            >
                                📋 Kelola Record
                            </Link>
                            <Link
                                href={route('training.records.create')}
                                className="px-3 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded hover:bg-green-200 dark:hover:bg-green-800/50"
                            >
                                ➕ Buat Record Baru
                            </Link>
                        </div>
                    </div>

                    {/* Info & Tips */}
                    <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div className="flex items-start gap-3">
                            <div className="text-2xl">💡</div>
                            <div className="flex-1">
                                <h4 className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">
                                    Tips Menggunakan Matrix
                                </h4>
                                <ul className="text-xs text-blue-700 dark:text-blue-300 space-y-1">
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
