import Pagination from '@/Components/Qhsse/Pagination';
import StatusBadge from '@/Components/Qhsse/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paginated } from '@/types/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface NumberingFormat {
    id: number;
    module_name: string;
    prefix: string;
    padding: number;
    separator: string;
    reset_frequency: string;
    include_year: boolean;
    include_site_code: boolean;
    sample?: string | null;
    is_active: boolean;
}

interface GeneratedNumber {
    id: number;
    module_name: string;
    number: string;
    site_code: string;
    year?: number | null;
    sequence: number;
    created_at: string;
}

export default function Index({ formats, recentNumbers, filters }: { formats: Paginated<NumberingFormat>; recentNumbers: GeneratedNumber[]; filters: { search?: string } }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const generatedNumber = (usePage().props.flash as { generated_number?: string } | undefined)?.generated_number;
    const { data, setData, post, processing, errors } = useForm({ module_name: 'incident', site_code: '', reference_type: '', reference_id: '' });

    function submitSearch(event: FormEvent) {
        event.preventDefault();
        router.get(route('core.numbering.index'), { search }, { preserveState: true });
    }

    function generate(event: FormEvent) {
        event.preventDefault();
        post(route('core.numbering.generate'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Numbering Service</h2>}>
            <Head title="Numbering Service" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {generatedNumber && <div className="rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">Generated number: {generatedNumber}</div>}

                    <div className="grid gap-6 lg:grid-cols-3">
                        <form onSubmit={generate} className="space-y-4 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">Generate Test Number</h3>
                            <Field label="Module" error={errors.module_name}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.module_name} onChange={(e) => setData('module_name', e.target.value)} /></Field>
                            <Field label="Site Code" error={errors.site_code}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.site_code} onChange={(e) => setData('site_code', e.target.value)} placeholder="Required only for site-based formats" /></Field>
                            <Field label="Reference Type" error={errors.reference_type}><input className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.reference_type} onChange={(e) => setData('reference_type', e.target.value)} /></Field>
                            <Field label="Reference ID" error={errors.reference_id}><input type="number" className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={data.reference_id} onChange={(e) => setData('reference_id', e.target.value)} /></Field>
                            <button disabled={processing} className="rounded-md bg-gray-900 px-4 py-2 text-white disabled:opacity-50 dark:bg-gray-100 dark:text-gray-900">Generate</button>
                        </form>

                        <div className="bg-white p-6 shadow-sm sm:rounded-lg lg:col-span-2 dark:bg-gray-800">
                            <div className="mb-4 flex flex-col justify-between gap-4 sm:flex-row">
                                <form onSubmit={submitSearch} className="flex gap-2">
                                    <input className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search formats" />
                                    <button className="rounded-md bg-gray-900 px-4 py-2 text-white dark:bg-gray-100 dark:text-gray-900">Search</button>
                                </form>
                                <Link href={route('core.numbering.create')} className="rounded-md bg-indigo-600 px-4 py-2 text-center text-white">New Format</Link>
                            </div>

                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900"><tr><th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Module</th><th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Format</th><th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Sample</th><th className="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th><th /></tr></thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {formats.data.map((format) => <tr key={format.id}><td className="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{format.module_name}</td><td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{format.prefix} / {format.reset_frequency}</td><td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{format.sample}</td><td className="px-4 py-3"><StatusBadge active={format.is_active} /></td><td className="px-4 py-3 text-right text-sm"><Link href={route('core.numbering.edit', format.id)} className="text-indigo-600 dark:text-indigo-400">Edit</Link></td></tr>)}
                                </tbody>
                            </table>
                            <Pagination links={formats.links} />
                        </div>
                    </div>

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <h3 className="mb-4 font-semibold text-gray-900 dark:text-gray-100">Recent Generated Numbers</h3>
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">{recentNumbers.map((number) => <div key={number.id} className="rounded border border-gray-200 p-3 text-sm dark:border-gray-700"><div className="font-medium text-gray-900 dark:text-gray-100">{number.number}</div><div className="text-xs text-gray-500">{number.module_name} seq {number.sequence}</div></div>)}</div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) { return <label className="block text-sm font-medium text-gray-700 dark:text-gray-200"><span>{label}</span><div className="mt-1">{children}</div>{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</label>; }
