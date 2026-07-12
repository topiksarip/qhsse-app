import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useState } from 'react';

interface CustomerComplaint {
    id: number;
    complaint_number: string;
    customer_name: string;
    customer_contact: string;
    title: string;
    description: string;
    product_service: string | null;
    status: 'open' | 'closed';
    resolution: string | null;
    closed_at: string | null;
    site: { id: number; name: string };
    severity: { id: number; name: string; color: string };
    ncr: { id: number; ncr_number: string } | null;
    created_at: string;
}

interface Props extends PageProps {
    complaint: CustomerComplaint;
    can: { update: boolean; close: boolean };
}

export default function Show({ complaint, can }: Props) {
    const [showCloseModal, setShowCloseModal] = useState(false);
    const [resolution, setResolution] = useState('');

    const handleClose = () => {
        router.post(route('quality.complaints.close', complaint.id), { resolution });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Complaint - ${complaint.complaint_number}`} />
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-start mb-6">
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">Detail Complaint</h1>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">Informasi lengkap complaint customer</p>
                        </div>
                        <div className="flex gap-3">
                            <Link href={route('quality.complaints.index')} className="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">
                                ← Kembali
                            </Link>
                            {complaint.status === 'open' && can.update && (
                                <Link href={route('quality.complaints.edit', complaint.id)} className="inline-flex items-center px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg text-sm font-medium">
                                    Edit
                                </Link>
                            )}
                            {complaint.status === 'open' && can.close && (
                                <button onClick={() => setShowCloseModal(true)} className="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">
                                    Tutup Complaint
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
                        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-medium text-slate-900 dark:text-white">{complaint.complaint_number}</h2>
                                    <p className="text-sm text-slate-600 dark:text-slate-400">{complaint.title}</p>
                                </div>
                                {complaint.status === 'open' ? (
                                    <span className="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Open</span>
                                ) : (
                                    <span className="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Closed</span>
                                )}
                            </div>
                        </div>

                        <div className="px-6 py-6 space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Nama Customer</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{complaint.customer_name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Kontak Customer</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{complaint.customer_contact}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Site</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{complaint.site.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Severity</dt>
                                    <dd className="mt-1"><span className={`px-2 py-1 text-xs font-medium rounded-full bg-${complaint.severity.color}-100 text-${complaint.severity.color}-800`}>{complaint.severity.name}</span></dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Produk/Layanan</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{complaint.product_service || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-slate-500 dark:text-slate-400">Tanggal Dibuat</dt>
                                    <dd className="mt-1 text-sm text-slate-900 dark:text-white">{new Date(complaint.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</dd>
                                </div>
                            </div>

                            <div className="border-t border-slate-200 dark:border-slate-700 pt-6">
                                <h3 className="text-sm font-medium text-slate-900 dark:text-white mb-2">Deskripsi Complaint</h3>
                                <p className="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{complaint.description}</p>
                            </div>

                            {complaint.resolution && (
                                <div className="border-t border-slate-200 dark:border-slate-700 pt-6">
                                    <h3 className="text-sm font-medium text-slate-900 dark:text-white mb-2">Resolusi</h3>
                                    <p className="text-sm text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{complaint.resolution}</p>
                                    {complaint.closed_at && (
                                        <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            Ditutup pada {new Date(complaint.closed_at).toLocaleString('id-ID', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {showCloseModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-slate-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-medium text-slate-900 dark:text-white mb-4">Tutup Complaint</h3>
                        <div className="mb-4">
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Resolusi *</label>
                            <textarea value={resolution} onChange={(e) => setResolution(e.target.value)} className="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white" rows={4} placeholder="Jelaskan bagaimana complaint ini diselesaikan..." required />
                        </div>
                        <div className="flex gap-3 justify-end">
                            <button onClick={() => setShowCloseModal(false)} className="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Batal</button>
                            <button onClick={handleClose} disabled={!resolution} className="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50">Tutup Complaint</button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
