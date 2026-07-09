import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Company, Employee } from '@/types/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type OptionCompany = Pick<Company, 'id' | 'name'>;

export default function Form({ employee, companies }: { employee: Employee | null; companies: OptionCompany[] }) {
    const isEdit = Boolean(employee);
    const { data, setData, post, put, processing, errors } = useForm({
        company_id: employee?.company_id?.toString() ?? '',
        employee_no: employee?.employee_no ?? '',
        name: employee?.name ?? '',
        email: employee?.email ?? '',
        phone: employee?.phone ?? '',
        department: employee?.department ?? '',
        position: employee?.position ?? '',
        is_active: employee?.is_active ?? true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        isEdit ? put(route('core.employees.update', employee!.id)) : post(route('core.employees.store'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Employee' : 'New Employee'}</h2>}>
            <Head title={isEdit ? 'Edit Employee' : 'New Employee'} />
            <div className="py-12"><div className="mx-auto max-w-3xl sm:px-6 lg:px-8"><form onSubmit={submit} className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Employee No" error={errors.employee_no}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.employee_no} onChange={(e) => setData('employee_no', e.target.value)} /></Field>
                    <Field label="Name" error={errors.name}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
                    <Field label="Company" error={errors.company_id}><select className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.company_id} onChange={(e) => setData('company_id', e.target.value)}><option value="">No company</option>{companies.map((company) => <option key={company.id} value={company.id}>{company.name}</option>)}</select></Field>
                    <Field label="Email" error={errors.email}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.email} onChange={(e) => setData('email', e.target.value)} /></Field>
                    <Field label="Phone" error={errors.phone}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.phone} onChange={(e) => setData('phone', e.target.value)} /></Field>
                    <Field label="Department" error={errors.department}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.department} onChange={(e) => setData('department', e.target.value)} /></Field>
                    <Field label="Position" error={errors.position}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.position} onChange={(e) => setData('position', e.target.value)} /></Field>
                    <label className="flex items-center gap-2 pt-8 text-sm text-gray-700 dark:text-gray-200"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Active</label>
                </div>
                <div className="flex justify-end gap-3"><Link href={route('core.employees.index')} className="rounded-md px-4 py-2 text-gray-700 dark:text-gray-200">Cancel</Link><button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Save</button></div>
            </form></div></div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="block text-sm font-medium text-gray-700 dark:text-gray-200"><span>{label}</span><div className="mt-1">{children}</div>{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</label>;
}
