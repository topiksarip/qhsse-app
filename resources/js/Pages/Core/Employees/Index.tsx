import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/Components/Qhsse/Pagination';
import StatusBadge from '@/Components/Qhsse/StatusBadge';
import { Employee, Paginated } from '@/types/core';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import DeleteWithConfirm from '@/Components/UI/DeleteWithConfirm';

export default function Index({ employees, filters }: { employees: Paginated<Employee>; filters: { search?: string } }) {
    const [search, setSearch] = useState(filters.search ?? '');

    function submit(event: FormEvent) {
        event.preventDefault();
        router.get(route('core.employees.index'), { search }, { preserveState: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Employees</h2>}>
            <Head title="Employees" />
            <div className="py-12"><div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row">
                    <form onSubmit={submit} className="flex gap-2"><input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search employees" /><button className="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Search</button></form>
                    <Link href={route('core.employees.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-center text-white">New Employee</Link>
                </div>
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"><table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-900"><tr><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">No</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Company</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Department</th><th className="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th><th className="px-6 py-3" /></tr></thead>
                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">{employees.data.map((employee) => <tr key={employee.id}><td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{employee.employee_no}</td><td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{employee.name}<div className="text-xs text-gray-500">{employee.email}</div></td><td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{employee.company?.name ?? '-'}</td><td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{employee.department ?? '-'}</td><td className="px-6 py-4"><StatusBadge active={employee.is_active} /></td><td className="px-6 py-4 text-right text-sm"><Link href={route('core.employees.edit', employee.id)} className="text-indigo-600 dark:text-indigo-400">Edit</Link><DeleteWithConfirm routeName="core.employees.destroy" id={employee.id} permission="core.employees.delete" itemLabel={employee.name} redirectTo="core.employees.index" asLink>Delete</DeleteWithConfirm></td></tr>)}</tbody>
                </table></div><Pagination links={employees.links} />
            </div></div>
        </AuthenticatedLayout>
    );
}
