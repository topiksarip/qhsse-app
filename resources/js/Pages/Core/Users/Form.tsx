import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Company, CoreUser, Employee } from '@/types/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type CompanyOption = Pick<Company, 'id' | 'name'>;
type EmployeeOption = Pick<Employee, 'id' | 'name' | 'employee_no' | 'company_id'>;
type RoleOption = { id: number; name: string };

export default function Form({ userRecord, companies, employees, roles, assignedRoles = [] }: { userRecord: CoreUser | null; companies: CompanyOption[]; employees: EmployeeOption[]; roles: RoleOption[]; assignedRoles?: string[] }) {
    const isEdit = Boolean(userRecord);
    const { data, setData, post, put, processing, errors } = useForm({
        company_id: userRecord?.company_id?.toString() ?? '',
        employee_id: userRecord?.employee_id?.toString() ?? '',
        name: userRecord?.name ?? '',
        email: userRecord?.email ?? '',
        password: '',
        is_active: userRecord?.is_active ?? true,
        roles: assignedRoles,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        isEdit ? put(route('core.users.update', userRecord!.id)) : post(route('core.users.store'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit User' : 'New User'}</h2>}>
            <Head title={isEdit ? 'Edit User' : 'New User'} />
            <div className="py-12"><div className="mx-auto max-w-3xl sm:px-6 lg:px-8"><form onSubmit={submit} className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Name" error={errors.name}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
                    <Field label="Email" error={errors.email}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.email} onChange={(e) => setData('email', e.target.value)} /></Field>
                    <Field label={isEdit ? 'New Password' : 'Password'} error={errors.password}><input type="password" className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.password} onChange={(e) => setData('password', e.target.value)} /></Field>
                    <Field label="Company" error={errors.company_id}><select className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.company_id} onChange={(e) => setData('company_id', e.target.value)}><option value="">No company</option>{companies.map((company) => <option key={company.id} value={company.id}>{company.name}</option>)}</select></Field>
                    <Field label="Employee" error={errors.employee_id}><select className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.employee_id} onChange={(e) => setData('employee_id', e.target.value)}><option value="">No employee</option>{employees.map((employee) => <option key={employee.id} value={employee.id}>{employee.employee_no} - {employee.name}</option>)}</select></Field>
                    <label className="flex items-center gap-2 pt-8 text-sm text-gray-700 dark:text-gray-200"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Active</label>
                </div>
                <div>
                    <p className="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">Roles</p>
                    <div className="grid gap-2 sm:grid-cols-2">
                        {roles.map((role) => (
                            <label key={role.id} className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={data.roles.includes(role.name)}
                                    onChange={(event) => {
                                        setData('roles', event.target.checked
                                            ? [...data.roles, role.name]
                                            : data.roles.filter((name) => name !== role.name));
                                    }}
                                />
                                {role.name}
                            </label>
                        ))}
                    </div>
                    {errors.roles && <p className="mt-1 text-sm text-red-600">{errors.roles}</p>}
                </div>
                <div className="flex justify-end gap-3"><Link href={route('core.users.index')} className="rounded-md px-4 py-2 text-gray-700 dark:text-gray-200">Cancel</Link><button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Save</button></div>
            </form></div></div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="block text-sm font-medium text-gray-700 dark:text-gray-200"><span>{label}</span><div className="mt-1">{children}</div>{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</label>;
}
