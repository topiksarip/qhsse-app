import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';

interface VisitorLog {
    id: number;
    visitor_name: string;
    visitor_company: string | null;
    visitor_type: string;
    visitor_id_number: string;
    visitor_phone: string | null;
    purpose: string;
    vehicle_number: string | null;
    checked_in_at: string;
    checked_out_at: string | null;
    status: 'checked_in' | 'checked_out';
    notes: string | null;
    site: { id: number; name: string };
    host_employee: { id: number; name: string };
    checked_in_by: { id: number; name: string };
    checked_out_by: { id: number; name: string } | null;
}

interface Props extends PageProps {
    visitor: VisitorLog;
    can: {
        update: boolean;
        checkOut: boolean;
    };
}

export default function Show({ visitor, can }: Props) {
    const handleCheckOut = () => {
        if (confirm('Apakah Anda yakin ingin check-out pengunjung ini?')) {
            router.post(route('security.visitors.check-out', visitor.id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Detail Pengunjung - ${visitor.visitor_name}`} />
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-start mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">
                                Detail Pengunjung
                            </h1>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                Informasi lengkap pengunjung
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <Link
                                href={route('security.visitors.index')}
                                className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                ← Kembali
                            </Link>
                            {visitor.status === 'checked_in' && can.update && (
                                <Link
                                    href={route('security.visitors.edit', visitor.id)}
                                    className="inline-flex items-center px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-sm font-medium"
                                >
                                    Edit
                                </Link>
                            )}
                            {visitor.status === 'checked_in' && can.checkOut && (
                                <button
                                    onClick={handleCheckOut}
                                    className="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium"
                                >
                                    Check-Out
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
                        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-medium text-slate-900 dark:text-white">
                                    {visitor.visitor_name}
                                </h2>
                                {visitor.status === 'checked_in' ? (
                                    <span className="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Di Lokasi
                                    </span>
                                ) : (
                                    <span className="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        Check-Out
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="px-6 py-6 space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Nama Pengunjung</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.visitor_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Perusahaan</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.visitor_company || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Jenis ID</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.visitor_type}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Nomor ID</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.visitor_id_number}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Telepon</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.visitor_phone || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Plat Kendaraan</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.vehicle_number || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Site</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.site.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Host</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.host_employee.name}</dd>
                                </div>
                                <div className="md:col-span-2">
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Tujuan Kunjungan</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.purpose}</dd>
                                </div>
                            </div>

                            <div className="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 className="text-sm font-medium text-slate-900 dark:text-white mb-4">Informasi Check-In/Out</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Waktu Check-In</dt>
                                        <dd className="mt-1 text-sm text-slate-900 dark:text-white">
                                            {new Date(visitor.checked_in_at).toLocaleString('id-ID', {
                                                weekday: 'long',
                                                day: '2-digit',
                                                month: 'long',
                                                year: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Petugas Check-In</dt>
                                        <dd className="mt-1 text-sm text-slate-900 dark:text-white">{visitor.checked_in_by.name}</dd>
                                    </div>
                                    {visitor.checked_out_at && (
                                        <>
                                            <div>
                                                <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Waktu Check-Out</dt>
                                                <dd className="mt-1 text-sm text-slate-900 dark:text-white">
                                                    {new Date(visitor.checked_out_at).toLocaleString('id-ID', {
                                                        weekday: 'long',
                                                        day: '2-digit',
                                                        month: 'long',
                                                        year: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    })}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Petugas Check-Out</dt>
                                                <dd className="mt-1 text-sm text-slate-900 dark:text-white">
                                                    {visitor.checked_out_by?.name || '—'}
                                                </dd>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>

                            {visitor.notes && (
                                <div className="border-t border-slate-200 dark:border-slate-700 pt-6">
                                    <h3 className="text-sm font-medium text-slate-900 dark:text-white mb-2">Catatan</h3>
                                    <p className="text-sm text-slate-600 dark:text-slate-400">{visitor.notes}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
