import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

type Item = { id: number; question: string; type: string; category: string | null; is_required: boolean; order: number };
type Template = { id: number; code: string; name: string; description: string | null; category: string; is_active: boolean; items: Item[] };

export default function TemplateShow({ template, auth }: PageProps<{ template: Template }>) {
    const permissions = new Set(auth.permissions ?? []);
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{template.code}</h2>}>
            <Head title={`Template ${template.code}`} />
            <div className="py-12"><div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div className="flex items-center gap-3">
                        <span className="text-2xl font-bold text-gray-900 dark:text-gray-100">{template.code}</span>
                        <span className="inline-flex rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800 dark:bg-purple-900/40 dark:text-purple-200">{template.category}</span>
                    </div>
                    <h1 className="mt-3 text-xl font-semibold text-gray-900 dark:text-gray-100">{template.name}</h1>
                    {template.description && <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">{template.description}</p>}
                </div>
                <div className="flex gap-2">
                    <Link href={route('inspection.templates.index')} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Kembali</Link>
                    {permissions.has('inspection.checklists.update') && <Link href={route('inspection.templates.edit', template.id)} className="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200">Edit</Link>}
                    {permissions.has('inspection.checklists.create') && <Link href={route('inspection.checklists.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buat Inspeksi dari Template</Link>}
                </div>
                <div className="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Items ({template.items.length})</h3>
                    <div className="space-y-3">
                        {template.items.map((item, i) => (
                            <div key={item.id} className="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{i + 1}. {item.question}</p>
                                        <p className="text-xs text-gray-500">Type: {item.type} {item.is_required && '• Required'} {item.category && `• ${item.category}`}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                        {template.items.length === 0 && <p className="text-sm text-gray-500 dark:text-gray-400">Belum ada items.</p>}
                    </div>
                </div>
            </div></div>
        </AuthenticatedLayout>
    );
}
