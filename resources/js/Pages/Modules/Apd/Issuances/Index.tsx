import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEvent, useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';
import FilterPanel from '@/Components/UI/FilterPanel';
import Pagination from '@/Components/Qhsse/Pagination';

interface Issuance {
    id: number;
    issue_number: string;
    item: { item_number: string; catalog?: { name: string } } | null;
    holder_label: string;
    quantity: number;
    status: string;
    issue_date: string | null;
    returned_date: string | null;
}

type Paginated<T> = {
    data: T[];
    links: any[];
};

type Props = {
    issuances: Paginated<Issuance>;
    filters: { search?: string; status?: string };
    statuses: Record<string, string>;
    holderTypes: Record<string, string>;
    can: { create: boolean; export: boolean };
};

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleDateString('id-ID') : '-');

export default function Index({ issuances, filters, statuses, holderTypes, can }: PageProps<Props>) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/apd/issuances', { search, status }, { preserveState: true });
    };
    const reset = () => {
        setSearch('');
        setStatus('');
        router.get('/apd/issuances');
    };

    const exportQuery = new URLSearchParams(
        Object.entries({ search, status }).reduce((acc, [k, v]) => {
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
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Penugasan APD</h2>
                    </div>
                    <div className="flex gap-2">
                        {can.export && (
                            <PrimaryButton size="sm" href={`/apd/issuances/export${exportQuery ? `?${exportQuery}` : ''}`}>Export</PrimaryButton>
                        )}
                        {can.create && <PrimaryButton size="sm" href="/apd/issuances/create">Issue / Ajukan</PrimaryButton>}
                    </div>
                </div>
            }
        >
            <Head title="Penugasan APD" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <FilterPanel activeCount={[search, status].filter((v) => v !== '').length}>
                        <form onSubmit={apply} className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input
                                type="text"
                                placeholder="Cari no. issue / catatan..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            />
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                                <option value="">Semua Status</option>
                                {Object.entries(statuses).map(([key, label]) => (
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
                                <th className="px-4 py-3">No. Issue</th>
                                <th className="px-4 py-3">Item</th>
                                <th className="px-4 py-3">Katalog</th>
                                <th className="px-4 py-3">Pemegang</th>
                                <th className="px-4 py-3 text-center">Qty</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Tgl Issue</th>
                                <th className="px-4 py-3">Tgl Kembali</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {issuances.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-4 py-12 text-center text-gray-500">
                                        Belum ada penugasan APD.
                                    </td>
                                </tr>
                            ) : (
                                issuances.data.map((iss) => (
                                    <tr key={iss.id} className="border-b border-slate-100 dark:border-gray-800">
                                        <td className="px-4 py-3"><Link href={`/apd/issuances/${iss.id}`} className="font-medium text-blue-600 hover:underline dark:text-blue-400">{iss.issue_number}</Link></td>
                                        <td className="px-4 py-3">{iss.item?.item_number ?? '-'}</td>
                                        <td className="px-4 py-3">{iss.item?.catalog?.name ?? '-'}</td>
                                        <td className="px-4 py-3">{iss.holder_label}</td>
                                        <td className="px-4 py-3 text-center">{iss.quantity}</td>
                                        <td className="px-4 py-3">{statuses[iss.status] || iss.status}</td>
                                        <td className="px-4 py-3">{formatDate(iss.issue_date)}</td>
                                        <td className="px-4 py-3">{formatDate(iss.returned_date)}</td>
                                    </tr>
                                ))
                            )}
                        </TableBody>
                    </TableWrapper>

                    <Pagination links={issuances.links} />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
