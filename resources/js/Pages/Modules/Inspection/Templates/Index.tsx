import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import { Paginated } from '@/types/core';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import EmptyState from '@/Components/UI/EmptyState';

type Template = { id: number; code: string; name: string; category: string; is_active: boolean; items_count: number };

export default function TemplateIndex({ items, auth }: PageProps<{ items: Paginated<Template> }>) {
    const permissions = new Set(auth.permissions ?? []);
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Template Inspeksi</h2>}>
            <Head title="Template Inspeksi" />
            <div className="py-12"><div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Template Inspeksi</h1>
                    {permissions.has('inspection.checklists.create') && <Link href={route('inspection.templates.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buat Template</Link>}
                </div>
                <div className="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                    <div className="overflow-x-auto"><table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-900"><tr>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Code</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Name</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Category</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Items</th>
                            <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Active</th>
                        </tr></thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-12">
                                        <EmptyState
                                            title="Belum ada template"
                                            description="Kelola template checklist inspeksi area, alat, dan proses"
                                            action={
                                                permissions.has('inspection.checklists.create') ? (
                                                    <Link
                                                        href={route('inspection.templates.create')}
                                                        className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                                    >
                                                        Buat Template
                                                    </Link>
                                                ) : undefined
                                            }
                                        />
                                    </td>
                                </tr>
                            ) : items.data.map((t) => (
                                    <tr key={t.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td className="px-4 py-3 text-sm"><Link href={route('inspection.templates.show', t.id)} className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{t.code}</Link></td>
                                        <td className="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">{t.name}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{t.category}</td>
                                        <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{t.items_count}</td>
                                        <td className="px-4 py-3 text-sm"><span className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${t.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'}`}>{t.is_active ? 'Active' : 'Inactive'}</span></td>
                                    </tr>
                                ))}
                        </tbody>
                    </table></div>
                </div>
                <Pagination links={items.links} />
            </div></div>
        </AuthenticatedLayout>
    );
}
