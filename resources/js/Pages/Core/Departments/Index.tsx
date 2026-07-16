import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import StatusBadge from '@/Components/Qhsse/StatusBadge';
import { Department, Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';
import FilterPanel from '@/Components/UI/FilterPanel';

type Filters = {
    search?: string;
    is_active?: string;
    sort?: string;
    direction?: string;
    per_page?: string;
};

export default function Index({ items, filters }: { items: Paginated<Department>; filters: Filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [isActive, setIsActive] = useState(filters.is_active ?? '');
    const [sort, setSort] = useState(filters.sort ?? 'name');
    const [direction, setDirection] = useState(filters.direction ?? 'asc');
    const [perPage, setPerPage] = useState(filters.per_page ?? '10');

    const query = { search, is_active: isActive, sort, direction, per_page: perPage };

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('core.departments.index'), query, { preserveState: true, replace: true });
    }

    function reset() {
        router.get(route('core.departments.index'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Departments</h2>}>
            <Head title="Departments" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, isActive, sort !== 'name', direction !== 'asc', perPage !== '10'].filter(Boolean).length}>
                        <div className="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                            <form onSubmit={submit} className="grid gap-3 md:grid-cols-6">
                                <input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 md:col-span-2" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search code or name" />
                                <select className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={isActive} onChange={(event) => setIsActive(event.target.value)}>
                                    <option value="">All Status</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <select className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={sort} onChange={(event) => setSort(event.target.value)}>
                                    <option value="name">Sort Name</option>
                                    <option value="code">Sort Code</option>
                                    <option value="created_at">Sort Created</option>
                                </select>
                                <select className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={direction} onChange={(event) => setDirection(event.target.value)}>
                                    <option value="asc">Ascending</option>
                                    <option value="desc">Descending</option>
                                </select>
                                <select className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={perPage} onChange={(event) => setPerPage(event.target.value)}>
                                    <option value="10">10/page</option>
                                    <option value="25">25/page</option>
                                    <option value="50">50/page</option>
                                </select>
                                <div className="flex flex-wrap gap-2 md:col-span-6">
                                    <button className="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Apply</button>
                                    <button type="button" onClick={reset} className="rounded-md border border-gray-300 px-4 py-2 text-gray-700 dark:border-gray-700 dark:text-gray-200">Reset</button>
                                    <a href={route('core.departments.export', query)} className="rounded-md border border-emerald-600 px-4 py-2 text-emerald-700 dark:text-emerald-300">Export CSV</a>
                                    <Link href={route('core.departments.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-center text-white">New Department</Link>
                                </div>
                            </form>
                        </div>
                    </FilterPanel>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900"><tr><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Site</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th><th className="px-6 py-3" /></tr></thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">{items.data.map((item) => <tr key={item.id}><td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.code}</td><td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.name}</td><td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{item.site?.name ?? '-'}</td><td className="px-6 py-4"><StatusBadge active={item.is_active} /></td><td className="px-6 py-4 text-right text-sm"><Link href={route('core.departments.edit', item.id)} className="text-indigo-600 dark:text-indigo-400">Edit</Link>
                                        <DeleteWithConfirm
                                            routeName="core.departments.destroy"
                                            id={item.id}
                                            permission="core.departments.delete"
                                            itemLabel={item.name}
                                            redirectTo="core.departments.index"
                                            asLink
                                        >
                                            Delete
                                        </DeleteWithConfirm>
                                    </td></tr>)}</tbody>
                        </table>
                    </div>
                    <Pagination links={items.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
