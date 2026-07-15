import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyContact, Site, PageProps, PaginatedData } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TableWrapper, { TableHead, TableBody } from '@/Components/UI/TableWrapper';

interface ContactIndexProps extends PageProps {
    contacts: PaginatedData<EmergencyContact>;
    filters: { search?: string; site_id?: number; is_active?: boolean };
    sites: Site[];
    can: { create: boolean; update: boolean };
}

const statusBadge = (active: boolean) =>
    active
        ? <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">Aktif</span>
        : <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">Nonaktif</span>;

export default function Index({ auth, contacts, filters, sites, can }: ContactIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');
    const [isActive, setIsActive] = useState(filters.is_active?.toString() || '');

    const handleFilter = () => router.get(route('emergency.contacts.index'), { search: search || undefined, site_id: siteId || undefined, is_active: isActive || undefined }, { preserveState: true, preserveScroll: true });
    const handleReset = () => { setSearch(''); setSiteId(''); setIsActive(''); router.get(route('emergency.contacts.index')); };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.28em] text-red-600 dark:text-red-400">Darurat</p>
                        <h2 className="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Kontak Darurat</h2>
                    </div>
                    {can.create && <PrimaryButton size="sm" href={route('emergency.contacts.create')} className="bg-red-600 hover:bg-red-700 focus:ring-red-500">+ Buat Kontak</PrimaryButton>}
                </div>
            }
        >
            <Head title="Kontak Darurat" />
            <div className="py-6">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <input type="text" placeholder="🔍 Cari nama, telepon..." value={search} onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && handleFilter()} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 md:col-span-2" />
                            <select value={siteId} onChange={(e) => setSiteId(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Site</option>
                                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                            <select value={isActive} onChange={(e) => setIsActive(e.target.value)} className="w-full rounded-md border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                <option value="">Semua Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                        <div className="mt-3 flex gap-2">
                            <PrimaryButton type="button" onClick={handleFilter}>Filter</PrimaryButton>
                            <SecondaryButton type="button" onClick={handleReset}>Reset</SecondaryButton>
                        </div>
                    </div>

                    <TableWrapper>
                        <TableHead>
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Peran</th>
                                <th className="px-4 py-3">Telepon</th>
                                <th className="px-4 py-3">Email</th>
                                <th className="px-4 py-3">Site</th>
                                <th className="px-4 py-3 text-center">Status</th>
                                <th className="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </TableHead>
                        <TableBody>
                            {contacts.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-4 py-12">
                                        <EmptyState title="Belum ada kontak darurat" description="Kelola direktori kontak darurat untuk emergency response per site" action={can.create ? <PrimaryButton href={route('emergency.contacts.create')} className="bg-red-600 hover:bg-red-700 focus:ring-red-500">+ Buat Kontak Pertama</PrimaryButton> : undefined} />
                                    </td>
                                </tr>
                            ) : contacts.data.map((contact) => (
                                <tr key={contact.id} className="hover:bg-slate-50 dark:hover:bg-gray-800">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-800 dark:text-slate-100">{contact.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{contact.role}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{contact.phone}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{contact.email || '—'}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{contact.site?.name}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">{statusBadge(contact.is_active)}</td>
                                    <td className="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        {can.update && <Link href={route('emergency.contacts.edit', contact.id)} className="text-emerald-600 hover:underline dark:text-emerald-400">Edit</Link>}
                                    </td>
                                </tr>
                            ))}
                        </TableBody>
                    </TableWrapper>

                    <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Menampilkan {contacts.from} – {contacts.to} dari {contacts.total} kontak</span>
                    </div>

                    {contacts.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-center gap-2">
                            {contacts.current_page > 1 && <Link href={route('emergency.contacts.index', { ...filters, page: contacts.current_page - 1 })} className="rounded-md border border-slate-300 px-3 py-1 text-sm text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">‹ Sebelumnya</Link>}
                            {[...Array(contacts.last_page)].map((_, i) => (
                                <Link key={i + 1} href={route('emergency.contacts.index', { ...filters, page: i + 1 })} className={`rounded-md border px-3 py-1 text-sm ${contacts.current_page === i + 1 ? 'border-red-600 bg-red-600 text-white' : 'border-slate-300 text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800'}`}>{i + 1}</Link>
                            ))}
                            {contacts.current_page < contacts.last_page && <Link href={route('emergency.contacts.index', { ...filters, page: contacts.current_page + 1 })} className="rounded-md border border-slate-300 px-3 py-1 text-sm text-gray-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Berikutnya ›</Link>}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
