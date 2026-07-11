import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { EmergencyContact, Site, PageProps } from '@/types';

interface CreateOrEditProps extends PageProps {
    contact?: EmergencyContact;
    sites: Site[];
}

export default function CreateOrEdit({ auth, contact, sites }: CreateOrEditProps) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: contact?.name || '',
        role: contact?.role || '',
        phone: contact?.phone || '',
        email: contact?.email || '',
        site_id: contact?.site_id?.toString() || '',
        is_active: contact?.is_active ?? true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (contact) {
            put(route('emergency.contacts.update', contact.id));
        } else {
            post(route('emergency.contacts.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {contact ? 'Edit Kontak Darurat' : 'Buat Kontak Darurat'}
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {contact ? 'Perbarui informasi kontak darurat' : 'Isi data kontak darurat dengan lengkap'}
                    </p>
                </div>
            }
        >
            <Head title={contact ? 'Edit Kontak Darurat' : 'Buat Kontak Darurat'} />

            <div className="py-6">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6 space-y-6">
                            <div>
                                <InputLabel htmlFor="name" value="Nama *" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    name="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="role" value="Peran *" />
                                <TextInput
                                    id="role"
                                    type="text"
                                    name="role"
                                    value={data.role}
                                    className="mt-1 block w-full"
                                    placeholder="Contoh: Fire Warden, First Aider, Site Security"
                                    onChange={(e) => setData('role', e.target.value)}
                                    required
                                />
                                <InputError message={errors.role} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="phone" value="Telepon *" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    name="phone"
                                    value={data.phone}
                                    className="mt-1 block w-full"
                                    placeholder="Contoh: +62-812-3456-7890"
                                    onChange={(e) => setData('phone', e.target.value)}
                                    required
                                />
                                <InputError message={errors.phone} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    placeholder="Contoh: kontak@example.com"
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="site_id" value="Site *" />
                                <select
                                    id="site_id"
                                    name="site_id"
                                    value={data.site_id}
                                    onChange={(e) => setData('site_id', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    required
                                >
                                    <option value="">— Pilih Site —</option>
                                    {sites.map((site) => (
                                        <option key={site.id} value={site.id}>
                                            {site.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.site_id} className="mt-2" />
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    name="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-red-600 dark:focus:ring-offset-gray-800"
                                />
                                <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                    Aktif
                                </label>
                            </div>

                            <div className="flex items-center justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('emergency.contacts.index')}
                                    className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    ← Batal
                                </Link>

                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
