import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface NumberingFormat {
    id: number;
    module_name: string;
    prefix: string;
    padding: number;
    separator: string;
    reset_frequency: string;
    include_year: boolean;
    include_site_code: boolean;
    is_active: boolean;
}

export default function Form({ format }: { format: NumberingFormat | null }) {
    const isEdit = Boolean(format);
    const { data, setData, post, put, processing, errors } = useForm({
        module_name: format?.module_name ?? '',
        prefix: format?.prefix ?? '',
        padding: format?.padding ?? 4,
        separator: format?.separator ?? '-',
        reset_frequency: format?.reset_frequency ?? 'yearly',
        include_year: format?.include_year ?? true,
        include_site_code: format?.include_site_code ?? false,
        is_active: format?.is_active ?? true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        isEdit ? put(route('core.numbering.update', format!.id)) : post(route('core.numbering.store'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{isEdit ? 'Edit Numbering Format' : 'New Numbering Format'}</h2>}>
            <Head title={isEdit ? 'Edit Numbering Format' : 'New Numbering Format'} />
            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Module Name" error={errors.module_name}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.module_name} onChange={(e) => setData('module_name', e.target.value)} /></Field>
                            <Field label="Prefix" error={errors.prefix}><input className="w-full rounded-md border-gray-300 uppercase dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.prefix} onChange={(e) => setData('prefix', e.target.value.toUpperCase())} /></Field>
                            <Field label="Padding" error={errors.padding}><input type="number" min="1" max="12" className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.padding} onChange={(e) => setData('padding', Number(e.target.value))} /></Field>
                            <Field label="Separator" error={errors.separator}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.separator} onChange={(e) => setData('separator', e.target.value)} /></Field>
                            <Field label="Reset Frequency" error={errors.reset_frequency}><select className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.reset_frequency} onChange={(e) => setData('reset_frequency', e.target.value)}><option value="yearly">Yearly</option><option value="never">Never</option></select></Field>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            <Check label="Include Year" checked={data.include_year} onChange={(value) => setData('include_year', value)} />
                            <Check label="Include Site Code" checked={data.include_site_code} onChange={(value) => setData('include_site_code', value)} />
                            <Check label="Active" checked={data.is_active} onChange={(value) => setData('is_active', value)} />
                        </div>

                        <div className="flex justify-end gap-3"><Link href={route('core.numbering.index')} className="text-sm text-gray-600 dark:text-gray-400">Cancel</Link><button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Save</button></div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) { return <label className="block text-sm font-medium text-gray-700 dark:text-gray-200"><span>{label}</span><div className="mt-1">{children}</div>{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</label>; }
function Check({ label, checked, onChange }: { label: string; checked: boolean; onChange: (value: boolean) => void }) { return <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200"><input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />{label}</label>; }
