import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps, PaginatedData, LegalRegister } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
import ComplianceStatusBadge from '@/Components/LegalCompliance/ComplianceStatusBadge';
import CategoryBadge from '@/Components/LegalCompliance/CategoryBadge';
import RegisterStatusBadge from '@/Components/LegalCompliance/RegisterStatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
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
    { value: 'national', label: 'Nasional' }, { value: 'regional', label: 'Regional' },
    { value: 'industry', label: 'Industri' }, { value: 'internal', label: 'Internal' },
];

const statuses: { value: string; label: string }[] = [
    { value: 'compliant', label: 'Patuh' }, { value: 'non_compliant', label: 'Tidak Patuh' },
    { value: 'in_progress', label: 'Dalam Proses' }, { value: 'not_applicable', label: 'Tidak Berlaku' },
];

export default function Index({ auth, registers, filters }: IndexProps) {
    const permissions = new Set(auth.permissions ?? []);
    const canCreate = permissions.has('legal.register.create');
    const canExport = permissions.has('legal.register.export');
    const canUpdate = permissions.has('legal.register.update');

    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [complianceStatus, setComplianceStatus] = useState(filters.compliance_status ?? '');

    function applyFilters() {
        router.get(route('legal.registers.index'), { search, category, compliance_status: complianceStatus }, { preserveState: true, replace: true });
    }
    function resetFilters() {
        setSearch(''); setCategory(''); setComplianceStatus('');
        router.get(route('legal.registers.index'), {}, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Kepatuhan</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Legal &amp; Compliance Register</h2>
                    </div>
                    <div className="flex gap-2">
                        {canExport && (
                            <SecondaryButton size="sm" href={route('legal.registers.export', { ...filters })}>Export CSV</SecondaryButton>
                        )}
                        {canCreate && (
                            <PrimaryButton size="sm" href={route('legal.registers.create')}>Buat Register</PrimaryButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Legal & Compliance Register" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && applyFilters()} placeholder="Cari nomor, judul, nama regulasi..." className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                            <select value={category} onChange={(e) => setCategory(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Kategori</option>
                                {categories.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                            </select>
                            <select value={complianceStatus} onChange={(e) => setComplianceStatus(e.target.value)} className="rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                {statuses.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                            </select>
                            <div className="flex gap-2">
                                <PrimaryButton type="button" onClick={applyFilters}>Filter</PrimaryButton>
                                <SecondaryButton type="button" onClick={resetFilters}>Reset</SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nomor</th>
                                <th className="px-4 py-3">Judul</th>
                                <th className="px-4 py-3">Kategori</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Owner</th>
                                <th className="px-4 py-3">Review</th>
                                <th className="px-4 py-3">Obligasi</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {registers.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12">
                                        <EmptyState
                                            title="Tidak ada register ditemukan"
                                            description="Kelola register peraturan dan regulasi kepatuhan QHSSE"
                                            action={canCreate ? <PrimaryButton href={route('legal.registers.create')}>Buat Register</PrimaryButton> : undefined}
                                        />
                                    </td>
                                </tr>
                            ) : registers.data.map((r) => {
                                const obligations = r.obligations ?? [];
                                const overdue = obligations.filter((o) => o.status === 'pending' && o.next_due && new Date(o.next_due) < new Date()).length;
                                const dueSoon = obligations.filter((o) => o.status === 'pending' && o.next_due && new Date(o.next_due) <= new Date(Date.now() + 7 * 24 * 60 * 60 * 1000) && new Date(o.next_due) >= new Date()).length;
                                return (
                                    <tr key={r.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                        <td className="whitespace-nowrap px-4 py-3 font-mono text-sm">
                                            <Link href={route('legal.registers.show', r.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{r.register_number}</Link>
                                        </td>
                                        <td className="px-4 py-3 text-sm text-slate-800 dark:text-slate-100">{r.title}</td>
                                        <td className="px-4 py-3"><CategoryBadge category={r.category} /></td>
                                        <td className="px-4 py-3"><ComplianceStatusBadge status={r.compliance_status} /></td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{r.owner?.name ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{r.next_review_date ?? '-'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{obligations.length > 0 ? `${obligations.length} (${overdue}🔴, ${dueSoon}🟠)` : '0'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                            <Link href={route('legal.registers.show', r.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">👁</Link>
                                            {canUpdate && (
                                                <Link href={route('legal.registers.edit', r.id)} className="ml-2 text-gray-600 hover:underline dark:text-gray-300">✏</Link>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </TableBody>
                    </TableWrapper>

                    {registers.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            <button disabled={registers.current_page <= 1} onClick={() => router.get(route('legal.registers.index'), { ...filters, page: registers.current_page - 1 }, { preserveState: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">‹ Sebelumnya</button>
                            <span className="text-sm text-gray-600 dark:text-gray-400">{registers.current_page} / {registers.last_page}</span>
                            <button disabled={registers.current_page >= registers.last_page} onClick={() => router.get(route('legal.registers.index'), { ...filters, page: registers.current_page + 1 }, { preserveState: true })} className="rounded-md bg-gray-100 px-3 py-1 text-sm text-gray-700 disabled:opacity-50 dark:bg-gray-800 dark:text-gray-200">Berikutnya ›</button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
