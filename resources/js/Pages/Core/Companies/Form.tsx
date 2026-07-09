import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Company } from '@/types/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Form({ company }: { company: Company | null }) {
    const isEdit = Boolean(company);
    const { data, setData, post, put, processing, errors } = useForm({
        code: company?.code ?? '',
        name: company?.name ?? '',
        type: company?.type ?? 'internal',
        email: company?.email ?? '',
        phone: company?.phone ?? '',
        address: company?.address ?? '',
        is_active: company?.is_active ?? true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        isEdit ? put(route('core.companies.update', company!.id)) : post(route('core.companies.store'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Company' : 'New Company'}</h2>}>
            <Head title={isEdit ? 'Edit Company' : 'New Company'} />
            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Code" error={errors.code}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.code} onChange={(e) => setData('code', e.target.value)} /></Field>
                            <Field label="Name" error={errors.name}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.name} onChange={(e) => setData('name', e.target.value)} /></Field>
                            <Field label="Type" error={errors.type}><select className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.type} onChange={(e) => setData('type', e.target.value as Company['type'])}><option value="internal">Internal</option><option value="contractor">Contractor</option><option value="vendor">Vendor</option></select></Field>
                            <Field label="Email" error={errors.email}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.email} onChange={(e) => setData('email', e.target.value)} /></Field>
                            <Field label="Phone" error={errors.phone}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.phone} onChange={(e) => setData('phone', e.target.value)} /></Field>
                            <label className="flex items-center gap-2 pt-8 text-sm text-gray-700 dark:text-gray-200"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Active</label>
                        </div>
                        <Field label="Address" error={errors.address}><textarea className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.address} onChange={(e) => setData('address', e.target.value)} /></Field>
                        <div className="flex justify-end gap-3"><Link href={route('core.companies.index')} className="rounded-md px-4 py-2 text-gray-700 dark:text-gray-200">Cancel</Link><button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Save</button></div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="block text-sm font-medium text-gray-700 dark:text-gray-200"><span>{label}</span><div className="mt-1">{children}</div>{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</label>;
}
