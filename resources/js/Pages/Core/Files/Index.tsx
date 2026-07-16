import Pagination from '@/Components/Qhsse/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import FilterPanel from '@/Components/UI/FilterPanel';

interface ManagedFile {
    id: number;
    module_name: string;
    reference_id: number;
    collection: string;
    original_name: string;
    mime_type: string;
    extension?: string | null;
    size: number;
    deleted_at?: string | null;
    uploader?: { id: number; name: string; email: string } | null;
}

export default function Index({ files, filters }: { files: Paginated<ManagedFile>; filters: { search?: string; module_name?: string; reference_id?: string; include_deleted?: boolean } }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [moduleName, setModuleName] = useState(filters.module_name ?? '');
    const [referenceId, setReferenceId] = useState(filters.reference_id ?? '');

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('core.files.index'), { search, module_name: moduleName, reference_id: referenceId }, { preserveState: true });
    }

    function removeFile(id: number) {
        if (confirm('Mark this file as deleted?')) {
            router.delete(route('core.files.destroy', id));
        }
    }

    function formatSize(bytes: number) {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Managed Files</h2>}>
            <Head title="Managed Files" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, moduleName, referenceId].filter(Boolean).length}>
                        <form onSubmit={submit} className="flex flex-col gap-2 sm:flex-row">
                            <input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search file" />
                            <input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={moduleName} onChange={(event) => setModuleName(event.target.value)} placeholder="module_name" />
                            <input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={referenceId} onChange={(event) => setReferenceId(event.target.value)} placeholder="reference_id" />
                            <button className="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Search</button>
                        </form>
                    </FilterPanel>
                    <div className="flex justify-end">
                        <Link href={route('core.files.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-center text-white">Upload File</Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">File</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Reference</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Size</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Uploaded By</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {files.data.map((file) => (
                                    <tr key={file.id}>
                                        <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            <div>{file.original_name}</div>
                                            <div className="text-xs text-gray-500">{file.mime_type}</div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            <div>{file.module_name} #{file.reference_id}</div>
                                            <div className="text-xs text-gray-500">{file.collection}</div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{formatSize(file.size)}</td>
                                        <td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{file.uploader?.name ?? '-'}</td>
                                        <td className="space-x-3 px-6 py-4 text-right text-sm">
                                            {!file.deleted_at && <a href={route('core.files.download', file.id)} className="text-indigo-600 dark:text-indigo-400">Download</a>}
                                            {!file.deleted_at && <button onClick={() => removeFile(file.id)} className="text-red-600 dark:text-red-400">Delete</button>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={files.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
