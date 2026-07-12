import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, LegalRegister } from '@/types';
import ComplianceStatusBadge from '@/Components/LegalCompliance/ComplianceStatusBadge';
import CategoryBadge from '@/Components/LegalCompliance/CategoryBadge';
import RegisterStatusBadge from '@/Components/LegalCompliance/RegisterStatusBadge';
import { useState } from 'react';

interface IndexProps extends PageProps {
    registers: PaginatedData<LegalRegister>;
    filters: {
        search?: string;
        category?: string;
        compliance_status?: string;
        site_id?: string;
        department_id?: string;
        owner_id?: string;
    };
}

const categories: { value: string; label: string }[] = [
    { value: 'national', label: 'Nasional' },
    { value: 'regional', label: 'Regional' },
    { value: 'industry', label: 'Industri' },
    { value: 'internal', label: 'Internal' },
];

const statuses: { value: string; label: string }[] = [
    { value: 'compliant', label: 'Patuh' },
    { value: 'non_compliant', label: 'Tidak Patuh' },
    { value: 'in_progress', label: 'Dalam Proses' },
    { value: 'not_applicable', label: 'Tidak Berlaku' },
];

export default function Index({ auth, registers, filters }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canCreate = permissions.has('legal.register.create');
    const canExport = permissions.has('legal.register.export');

    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [complianceStatus, setComplianceStatus] = useState(filters.compliance_status ?? '');

    function applyFilters() {
        router.get(route('legal.registers.index'), { search, category, compliance_status: complianceStatus }, { preserveState: true, replace: true });
    }

    function resetFilters() {
        setSearch('');
        setCategory('');
        setComplianceStatus('');
        router.get(route('legal.registers.index'), {}, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Legal &amp; Compliance Register</h2>}
        >
            <Head title="Legal & Compliance Register" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">Kelola register peraturan dan regulasi kepatuhan</p>
                        <div className="flex gap-2">
                            {canExport && (
                                <a href={route('legal.registers.export', { ...filters })} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">
                                    ⬇ Export CSV
                                </a>
                            )}
                            {canCreate && (
                                <Link href={route('legal.registers.create')} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    + Buat Register
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="mb-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                placeholder="Cari nomor, judul, nama regulasi..."
                                className="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm"
                            />
                            <select value={category} onChange={(e) => setCategory(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                <option value="">Semua Kategori</option>
                                {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                            </select>
                            <select value={complianceStatus} onChange={(e) => setComplianceStatus(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm">
                                <option value="">Semua Status</option>
                                {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </select>
                            <div className="flex gap-2">
                                <button onClick={applyFilters} className="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                                <button onClick={resetFilters} className="rounded-md bg-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Nomor</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Judul</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Kategori</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Owner</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Review</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Obligasi</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {registers.data.length === 0 ? (
                                    <tr><td colSpan={8} className="px-4 py-10 text-center text-sm text-gray-500">Tidak ada register ditemukan.</td></tr>
                                ) : (
                                    registers.data.map((r) => {
                                        const obligations = r.obligations ?? [];
                                        const overdue = obligations.filter((o) => o.status === 'pending' && o.next_due && new Date(o.next_due) < new Date()).length;
                                        const dueSoon = obligations.filter((o) => o.status === 'pending' && o.next_due && new Date(o.next_due) <= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000) && new Date(o.next_due) >= new Date()).length;
                                        return (
                                            <tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td className="px-4 py-3 text-sm font-mono text-gray-900 dark:text-gray-100">{r.register_number}</td>
                                                <td className="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{r.title}</td>
                                                <td className="px-4 py-3"><CategoryBadge category={r.category} /></td>
                                                <td className="px-4 py-3"><ComplianceStatusBadge status={r.compliance_status} /></td>
                                                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{r.owner?.name ?? '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{r.next_review_date ?? '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                    {obligations.length > 0 ? `${obligations.length} (${overdue}🔴, ${dueSoon}🟠)` : '0'}
                                                </td>
                                                <td className="px-4 py-3 text-right text-sm">
                                                    <Link href={route('legal.registers.show', r.id)} className="text-indigo-600 hover:underline dark:text-indigo-400">Lihat</Link>
                                                    {permissions.has('legal.register.update') && (
                                                        <>{' · '}<Link href={route('legal.registers.edit', r.id)} className="text-indigo-600 hover:underline dark:text-indigo-400">Edit</Link></>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {registers.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>Menampilkan {registers.from}–{registers.to} dari {registers.total} register</span>
                            <div className="flex gap-1">
                                <button
                                    disabled={registers.current_page <= 1}
                                    onClick={() => router.get(route('legal.registers.index'), { ...filters, page: registers.current_page - 1 }, { preserveState: true })}
                                    className="rounded px-3 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    ‹ Sebelumnya
                                </button>
                                <span className="px-3 py-1">{registers.current_page} / {registers.last_page}</span>
                                <button
                                    disabled={registers.current_page >= registers.last_page}
                                    onClick={() => router.get(route('legal.registers.index'), { ...filters, page: registers.current_page + 1 }, { preserveState: true })}
                                    className="rounded px-3 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    Berikutnya ›
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
