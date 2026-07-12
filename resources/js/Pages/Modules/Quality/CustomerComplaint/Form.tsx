import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { PageProps } from '@/types';

interface CustomerComplaint {
    id: number;
    complaint_number: string;
    customer_name: string;
    customer_contact: string;
    title: string;
    description: string;
    site_id: number;
    product_service: string | null;
    severity_id: number;
}

interface Props extends PageProps {
    complaint: CustomerComplaint | null;
    sites: Array<{ id: number; name: string }>;
    severities: Array<{ id: number; name: string; color: string }>;
}

export default function Form({ complaint, sites, severities }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        customer_name: complaint?.customer_name || '',
        customer_contact: complaint?.customer_contact || '',
        title: complaint?.title || '',
        description: complaint?.description || '',
        site_id: complaint?.site_id || '',
        product_service: complaint?.product_service || '',
        severity_id: complaint?.severity_id || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (complaint) {
            put(route('quality.complaints.update', complaint.id));
        } else {
            post(route('quality.complaints.store'));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={complaint ? 'Edit Complaint' : 'Catat Complaint'} />
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">
                            {complaint ? 'Edit Complaint Customer' : 'Catat Complaint Customer'}
                        </h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            {complaint ? 'Perbarui informasi complaint' : 'Daftarkan complaint dari customer'}
                        </p>
                    </div>

                    <form onSubmit={submit} className="bg-white dark:bg-slate-800 rounded-lg shadow p-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="customer_name" value="Nama Customer *" />
                                <TextInput
                                    id="customer_name"
                                    type="text"
                                    value={data.customer_name}
                                    onChange={(e) => setData('customer_name', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Nama lengkap customer"
                                    required
                                />
                                <InputError message={errors.customer_name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="customer_contact" value="Kontak Customer *" />
                                <TextInput
                                    id="customer_contact"
                                    type="text"
                                    value={data.customer_contact}
                                    onChange={(e) => setData('customer_contact', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Email atau telepon"
                                    required
                                />
                                <InputError message={errors.customer_contact} className="mt-2" />
                            </div>

                            <div className="md:col-span-2">
                                <InputLabel htmlFor="title" value="Judul Complaint *" />
                                <TextInput
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Ringkasan masalah"
                                    required
                                />
                                <InputError message={errors.title} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="site_id" value="Site *" />
                                <select
                                    id="site_id"
                                    value={data.site_id}
                                    onChange={(e) => setData('site_id', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    required
                                >
                                    <option value="">Pilih Site</option>
                                    {sites.map((site) => (
                                        <option key={site.id} value={site.id}>{site.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.site_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="severity_id" value="Severity *" />
                                <select
                                    id="severity_id"
                                    value={data.severity_id}
                                    onChange={(e) => setData('severity_id', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    required
                                >
                                    <option value="">Pilih Severity</option>
                                    {severities.map((sev) => (
                                        <option key={sev.id} value={sev.id}>{sev.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.severity_id} className="mt-2" />
                            </div>

                            <div className="md:col-span-2">
                                <InputLabel htmlFor="product_service" value="Produk/Layanan Terkait" />
                                <TextInput
                                    id="product_service"
                                    type="text"
                                    value={data.product_service}
                                    onChange={(e) => setData('product_service', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Nama produk atau layanan yang dikomplain"
                                />
                                <InputError message={errors.product_service} className="mt-2" />
                            </div>

                            <div className="md:col-span-2">
                                <InputLabel htmlFor="description" value="Deskripsi Complaint *" />
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    rows={5}
                                    placeholder="Jelaskan detail complaint dari customer..."
                                    required
                                />
                                <InputError message={errors.description} className="mt-2" />
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Minimal 20 karakter</p>
                            </div>
                        </div>

                        <div className="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                            <Link
                                href={route('quality.complaints.index')}
                                className="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                ← Batal
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {complaint ? 'Perbarui' : 'Simpan'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
