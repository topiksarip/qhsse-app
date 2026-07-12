import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyContact, Site, PageProps, PaginatedData } from '@/types';
import EmptyState from '@/Components/UI/EmptyState';

interface ContactIndexProps extends PageProps {
    contacts: PaginatedData<EmergencyContact>;
    filters: {
        search?: string;
        site_id?: number;
        is_active?: boolean;
    };
    sites: Site[];
    can: {
        create: boolean;
        update: boolean;
    };
}

export default function Index({ auth, contacts, filters, sites, can }: ContactIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [siteId, setSiteId] = useState(filters.site_id?.toString() || '');
    const [isActive, setIsActive] = useState(filters.is_active?.toString() || '');

    const handleFilter = () => {
        router.get(route('emergency.contacts.index'), {
            search: search || undefined,
            site_id: siteId || undefined,
            is_active: isActive || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setSiteId('');
        setIsActive('');
        router.get(route('emergency.contacts.index'));
    };

    const getStatusBadge = (active: boolean) => {
        if (active) {
            return (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Aktif
                </span>
            );
        }
        return (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                Nonaktif
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            Kontak Darurat
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Kelola direktori kontak darurat per site
                        </p>
                    </div>
                    {can.create && (
                        <Link
                            href={route('emergency.contacts.create')}
                            className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            + Buat Kontak
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Kontak Darurat" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filter Bar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="md:col-span-2">
                                    <input
                                        type="text"
                                        placeholder="🔍 Cari nama, telepon..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleFilter()}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </div>
                                <div>
                                    <select
                                        value={siteId}
                                        onChange={(e) => setSiteId(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="">Semua Site</option>
                                        {sites.map((site) => (
                                            <option key={site.id} value={site.id}>
                                                {site.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <select
                                        value={isActive}
                                        onChange={(e) => setIsActive(e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        <option value="">Semua Status</option>
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                            <div className="mt-4 flex gap-2">
                                <button
                                    onClick={handleFilter}
                                    className="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Filter
                                </button>
                                <button
                                    onClick={handleReset}
                                    className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {contacts.data.length === 0 ? (
                                <div className="py-12">
                                    <EmptyState
                                        title="Belum ada kontak darurat"
                                        description="Kelola direktori kontak darurat untuk emergency response per site"
                                        action={
                                            can.create ? (
                                                <Link
                                                    href={route('emergency.contacts.create')}
                                                    className="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                                                >
                                                    + Buat Kontak Pertama
                                                </Link>
                                            ) : undefined
                                        }
                                    />
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead className="bg-gray-50 dark:bg-gray-900">
                                                <tr>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Nama
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Peran
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Telepon
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Email
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Site
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                        Aksi
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                {contacts.data.map((contact) => (
                                                    <tr key={contact.id}>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {contact.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {contact.role}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {contact.phone}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {contact.email || '—'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {contact.site?.name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm">
                                                            {getStatusBadge(contact.is_active)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                            {can.update && (
                                                                <Link
                                                                    href={route('emergency.contacts.edit', contact.id)}
                                                                    className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                                                >
                                                                    Edit
                                                                </Link>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Pagination */}
                                    {contacts.last_page > 1 && (
                                        <div className="mt-6 flex items-center justify-between">
                                            <div className="text-sm text-gray-700 dark:text-gray-300">
                                                Menampilkan {contacts.from} - {contacts.to} dari {contacts.total} kontak
                                            </div>
                                            <div className="flex gap-2">
                                                {contacts.current_page > 1 && (
                                                    <Link
                                                        href={route('emergency.contacts.index', { ...filters, page: contacts.current_page - 1 })}
                                                        className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        ‹ Sebelumnya
                                                    </Link>
                                                )}
                                                {[...Array(contacts.last_page)].map((_, i) => (
                                                    <Link
                                                        key={i + 1}
                                                        href={route('emergency.contacts.index', { ...filters, page: i + 1 })}
                                                        className={`px-3 py-1 border rounded-md text-sm ${
                                                            contacts.current_page === i + 1
                                                                ? 'bg-red-600 text-white border-red-600'
                                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                                        }`}
                                                    >
                                                        {i + 1}
                                                    </Link>
                                                ))}
                                                {contacts.current_page < contacts.last_page && (
                                                    <Link
                                                        href={route('emergency.contacts.index', { ...filters, page: contacts.current_page + 1 })}
                                                        className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Berikutnya ›
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
