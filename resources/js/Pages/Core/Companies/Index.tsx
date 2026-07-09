import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import StatusBadge from '@/Components/Qhsse/StatusBadge';
import { Company, Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

export default function Index({ companies, filters }: { companies: Paginated<Company>; filters: { search?: string } }) {
    const [search, setSearch] = useState(filters.search ?? '');

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('core.companies.index'), { search }, { preserveState: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Companies</h2>}>
            <Head title="Companies" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row">
                        <form onSubmit={submit} className="flex gap-2">
                            <input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search companies" />
                            <button className="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Search</button>
                        </form>
                        <Link href={route('core.companies.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-center text-white">New Company</Link>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {companies.data.map((company) => (
                                    <tr key={company.id}>
                                        <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{company.code}</td>
                                        <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{company.name}</td>
                                        <td className="px-6 py-4 text-sm capitalize text-gray-700 dark:text-gray-300">{company.type}</td>
                                        <td className="px-6 py-4"><StatusBadge active={company.is_active} /></td>
                                        <td className="px-6 py-4 text-right text-sm"><Link href={route('core.companies.edit', company.id)} className="text-indigo-600 dark:text-indigo-400">Edit</Link></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination links={companies.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
