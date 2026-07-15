import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import { Paginated } from '@/types/core';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';

type Template = { id: number; code: string; name: string; category: string; is_active: boolean; items_count: number };

export default function TemplateIndex({ items, auth }: PageProps<{ items: Paginated<Template> }>) {
    const permissions = new Set(auth.permissions ?? []);
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-400">Inspeksi</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Template Inspeksi</h2>
                    </div>
                    {permissions.has('inspection.checklists.create') && <PrimaryButton size="sm" href={route('inspection.templates.create')}>Buat Template</PrimaryButton>}
                </div>
            }
        >
            <Head title="Template Inspeksi" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Code</th>
                                <th className="px-4 py-3">Name</th>
                                <th className="px-4 py-3">Category</th>
                                <th className="px-4 py-3">Items</th>
                                <th className="px-4 py-3">Active</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-12">
                                        <EmptyState title="Belum ada template" description="Kelola template checklist inspeksi area, alat, dan proses" action={permissions.has('inspection.checklists.create') ? <PrimaryButton href={route('inspection.templates.create')}>Buat Template</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : items.data.map((t) => (
                                <tr key={t.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="px-4 py-3 text-sm">
                                        <Link href={route('inspection.templates.show', t.id)} className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">{t.code}</Link>
                                        <Link href={route('inspection.templates.edit', t.id)} className="ml-3 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Edit</Link>
                                        <DeleteWithConfirm routeName="inspection.templates.destroy" id={t.id} permission="inspection.checklists.delete" itemLabel={t.name} asLink className="ml-3 text-red-600 hover:underline dark:text-red-400">🗑 Hapus</DeleteWithConfirm>
                                    </td>
                                    <td className="px-4 py-3 text-sm text-slate-800 dark:text-slate-200">{t.name}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{t.category}</td>
                                    <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{t.items_count}</td>
                                    <td className="px-4 py-3 text-sm">
                                        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${t.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'}`}>{t.is_active ? 'Active' : 'Inactive'}</span>
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>
                    <Pagination links={items.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
