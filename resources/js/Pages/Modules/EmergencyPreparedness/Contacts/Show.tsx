import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmergencyContact, Site, PageProps } from '@/types';

interface ShowProps extends PageProps {
    contact: EmergencyContact & {
        site?: Site;
    };
    can: {
        update: boolean;
        delete: boolean;
    };
}

export default function Show({ auth, contact, can }: ShowProps) {
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
                            Detail Kontak Darurat
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Informasi lengkap kontak darurat
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link
                            href={route('emergency.contacts.index')}
                            className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                        >
                            ← Kembali
                        </Link>
                        {can.update && (
                            <Link
                                href={route('emergency.contacts.edit', contact.id)}
                                className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                            >
                                Edit
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Kontak Darurat: ${contact.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Main Info */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Nama</h3>
                                    <p className="mt-1 text-base text-gray-900 dark:text-gray-100">{contact.name}</p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                                    <div className="mt-1">{getStatusBadge(contact.is_active)}</div>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Role</h3>
                                    <p className="mt-1 text-base text-gray-900 dark:text-gray-100">{contact.role}</p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Site</h3>
                                    <p className="mt-1 text-base text-gray-900 dark:text-gray-100">
                                        {contact.site?.name || '-'}
                                    </p>
                                </div>

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Telepon</h3>
                                    <p className="mt-1 text-base text-gray-900 dark:text-gray-100">
                                        <a href={`tel:${contact.phone}`} className="text-red-600 hover:text-red-700">
                                            {contact.phone}
                                        </a>
                                    </p>
                                </div>

                                {contact.mobile && (
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Mobile</h3>
                                        <p className="mt-1 text-base text-gray-900 dark:text-gray-100">
                                            <a href={`tel:${contact.mobile}`} className="text-red-600 hover:text-red-700">
                                                {contact.mobile}
                                            </a>
                                        </p>
                                    </div>
                                )}

                                {contact.email && (
                                    <div>
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Email</h3>
                                        <p className="mt-1 text-base text-gray-900 dark:text-gray-100">
                                            <a
                                                href={`mailto:${contact.email}`}
                                                className="text-red-600 hover:text-red-700"
                                            >
                                                {contact.email}
                                            </a>
                                        </p>
                                    </div>
                                )}

                                {contact.address && (
                                    <div className="md:col-span-2">
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Alamat</h3>
                                        <p className="mt-1 text-base text-gray-900 dark:text-gray-100">
                                            {contact.address}
                                        </p>
                                    </div>
                                )}

                                {contact.notes && (
                                    <div className="md:col-span-2">
                                        <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">Catatan</h3>
                                        <p className="mt-1 text-base text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                                            {contact.notes}
                                        </p>
                                    </div>
                                )}

                                <div>
                                    <h3 className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Prioritas Urutan
                                    </h3>
                                    <p className="mt-1 text-base text-gray-900 dark:text-gray-100">
                                        {contact.display_order}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Metadata */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Metadata</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="text-gray-500 dark:text-gray-400">Dibuat:</span>
                                    <span className="ml-2 text-gray-900 dark:text-gray-100">
                                        {new Date(contact.created_at).toLocaleString('id-ID')}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-gray-500 dark:text-gray-400">Diperbarui:</span>
                                    <span className="ml-2 text-gray-900 dark:text-gray-100">
                                        {new Date(contact.updated_at).toLocaleString('id-ID')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
