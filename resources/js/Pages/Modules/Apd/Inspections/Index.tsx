import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import FilterPanel from '@/Components/UI/FilterPanel';
import Pagination from '@/Components/Qhsse/Pagination';

interface Inspection {
    id: number;
    item: { item_number: string; catalog?: { name: string } } | null;
    inspection_type: string;
    inspection_date: string | null;
    result: string;
    condition: string | null;
    inspector?: { name: string } | null;
    next_inspection_date: string | null;
}

type Paginated<T> = {
    data: T[];
    links: any[];
};

type Props = {
    inspections: Paginated<Inspection>;
    filters: { search?: string; result?: string; inspection_type?: string };
    results: Record<string, string>;
    inspectionTypes: Record<string, string>;
    can: { create: boolean; export: boolean };
};

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString('id-ID') : '-');
const resultClass = (r: string) =>
    r === 'tidak_layak'
        ? 'inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300'
        : 'inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';

export default function Index({ inspections, filters, results, inspectionTypes, can }: PageProps<Props>) {
    const [search, setSearch] = useState(filters.search || '');
    const [result, setResult] = useState(filters.result || '');
    const [inspectionType, setInspectionType] = useState(filters.inspection_type || '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/apd/inspections', { search, result, inspection_type: inspectionType }, { preserveState: true });
    };
    const reset = () => {
        setSearch('');
        setResult('');
        setInspectionType('');
        router.get('/apd/inspections');
    };

    const exportQuery = new URLSearchParams(
        Object.entries({ search, result, inspection_type: inspectionType }).reduce((acc, [k, v]) => {
            if (v) acc[k] = v;
            return acc;
        }, {} as Record<string, string>),
    ).toString();

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-slate-600 dark:text-slate-400">APD / PPE</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Inspeksi APD</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && (
                            <PrimaryButton size="sm" href={`/apd/inspections/export${exportQuery ? `?${exportQuery}` : ''}`}>Export</PrimaryButton>
                        )}
                        {can.create && <PrimaryButton size="sm" href="/apd/inspections/create">Inspeksi Baru</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Inspeksi APD" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, result, inspectionType].filter((v) => v !== '').length}>
                        <form onSubmit={apply} className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input
                                type="text"
                                placeholder="Cari hasil / catatan..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <select
                                value={result}
                                onChange={(e) => setResult(e.target.value)}
                                className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">Semua Hasil</option>
                                {Object.entries(results).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                            <select
                                value={inspectionType}
                                onChange={(e) => setInspectionType(e.target.value)}
                                className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">Semua Tipe</option>
                                {Object.entries(inspectionTypes).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                            <div className="flex gap-2">
                                <PrimaryButton type="submit">Filter</PrimaryButton>
                                <PrimaryButton type="button" onClick={reset}>Reset</PrimaryButton>
                            </div>
                        </form>
                    </FilterPanel>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">No. Item</th>
                                <th className="px-4 py-3">Katalog</th>
                                <th className="px-4 py-3">Tipe</th>
                                <th className="px-4 py-3">Tgl Inspeksi</th>
                                <th className="px-4 py-3">Hasil</th>
                                <th className="px-4 py-3">Kondisi</th>
                                <th className="px-4 py-3">Inspektor</th>
                                <th className="px-4 py-3">Next</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {inspections.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12 text-center text-gray-500">
                                        Belum ada inspeksi APD.
                                    </td>
                                </tr>
                            ) : (
                                inspections.data.map((ins) => (
                                    <tr key={ins.id} className="border-b border-slate-100 dark:border-gray-800">
                                        <td className="px-4 py-3"><Link href={`/apd/inspections/${ins.id}`} className="font-medium text-blue-600 hover:underline dark:text-blue-400">{ins.item?.item_number ?? '-'}</Link></td>
                                        <td className="px-4 py-3">{ins.item?.catalog?.name ?? '-'}</td>
                                        <td className="px-4 py-3">{inspectionTypes[ins.inspection_type] || ins.inspection_type}</td>
                                        <td className="px-4 py-3">{formatDate(ins.inspection_date)}</td>
                                        <td className="px-4 py-3"><span className={resultClass(ins.result)}>{results[ins.result] || ins.result}</span></td>
                                        <td className="px-4 py-3">{ins.condition ?? '-'}</td>
                                        <td className="px-4 py-3">{ins.inspector?.name ?? '-'}</td>
                                        <td className="px-4 py-3">{formatDate(ins.next_inspection_date)}</td>
                                    </tr>
                                ))
                            )}
                        </TableBody>
                    </TableWrapper>

                    <Pagination links={inspections.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
