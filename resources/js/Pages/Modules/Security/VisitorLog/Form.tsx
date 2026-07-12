import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { PageProps } from '@/types';

interface Employee {
    id: number;
    name: string;
    user_id: number | null;
}

interface Site {
    id: number;
    name: string;
}

interface VisitorLog {
    id: number;
    visitor_name: string;
    visitor_type: string;
    visitor_id_number: string;
    visitor_company: string | null;
    visitor_phone: string | null;
    host_employee_id: number;
    site_id: number;
    purpose: string;
    vehicle_number: string | null;
    checked_in_at: string;
    notes: string | null;
}

interface Props extends PageProps {
    visitor: VisitorLog | null;
    sites: Site[];
    employees: Employee[];
}

export default function Form({ visitor, sites, employees }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        visitor_name: visitor?.visitor_name || '',
        visitor_type: visitor?.visitor_type || 'KTP',
        visitor_id_number: visitor?.visitor_id_number || '',
        visitor_company: visitor?.visitor_company || '',
        visitor_phone: visitor?.visitor_phone || '',
        host_employee_id: visitor?.host_employee_id || '',
        site_id: visitor?.site_id || '',
        purpose: visitor?.purpose || '',
        vehicle_number: visitor?.vehicle_number || '',
        checked_in_at: visitor?.checked_in_at || new Date().toISOString().slice(0, 16),
        notes: visitor?.notes || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (visitor) {
            put(route('security.visitors.update', visitor.id));
        } else {
            post(route('security.visitors.store'));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={visitor ? 'Edit Pengunjung' : 'Check-In Pengunjung'} />
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-semibold text-slate-900 dark:text-white">
                            {visitor ? 'Edit Data Pengunjung' : 'Check-In Pengunjung'}
                        </h1>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            {visitor ? 'Perbarui informasi pengunjung' : 'Daftarkan pengunjung yang masuk ke lokasi'}
                        </p>
                    </div>

                    <form onSubmit={submit} className="bg-white dark:bg-slate-800 rounded-lg shadow p-6 space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="visitor_name" value="Nama Pengunjung *" />
                                <TextInput
                                    id="visitor_name"
                                    type="text"
                                    value={data.visitor_name}
                                    onChange={(e) => setData('visitor_name', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Masukkan nama lengkap"
                                    required
                                />
                                <InputError message={errors.visitor_name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="visitor_company" value="Perusahaan" />
                                <TextInput
                                    id="visitor_company"
                                    type="text"
                                    value={data.visitor_company}
                                    onChange={(e) => setData('visitor_company', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Nama perusahaan/organisasi"
                                />
                                <InputError message={errors.visitor_company} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="visitor_type" value="Jenis ID *" />
                                <select
                                    id="visitor_type"
                                    value={data.visitor_type}
                                    onChange={(e) => setData('visitor_type', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    required
                                >
                                    <option value="KTP">KTP</option>
                                    <option value="SIM">SIM</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                <InputError message={errors.visitor_type} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="visitor_id_number" value="Nomor ID *" />
                                <TextInput
                                    id="visitor_id_number"
                                    type="text"
                                    value={data.visitor_id_number}
                                    onChange={(e) => setData('visitor_id_number', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Masukkan nomor ID"
                                    required
                                />
                                <InputError message={errors.visitor_id_number} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="visitor_phone" value="Telepon" />
                                <TextInput
                                    id="visitor_phone"
                                    type="tel"
                                    value={data.visitor_phone}
                                    onChange={(e) => setData('visitor_phone', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="+62xxx"
                                />
                                <InputError message={errors.visitor_phone} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="vehicle_number" value="Plat Kendaraan" />
                                <TextInput
                                    id="vehicle_number"
                                    type="text"
                                    value={data.vehicle_number}
                                    onChange={(e) => setData('vehicle_number', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="B 1234 ABC"
                                />
                                <InputError message={errors.vehicle_number} className="mt-2" />
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
                                <InputLabel htmlFor="host_employee_id" value="Host *" />
                                <select
                                    id="host_employee_id"
                                    value={data.host_employee_id}
                                    onChange={(e) => setData('host_employee_id', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    required
                                >
                                    <option value="">Pilih Host</option>
                                    {employees.map((emp) => (
                                        <option key={emp.id} value={emp.id}>{emp.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.host_employee_id} className="mt-2" />
                            </div>

                            <div className="md:col-span-2">
                                <InputLabel htmlFor="purpose" value="Tujuan Kunjungan *" />
                                <textarea
                                    id="purpose"
                                    value={data.purpose}
                                    onChange={(e) => setData('purpose', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    rows={3}
                                    placeholder="Jelaskan tujuan kunjungan..."
                                    required
                                />
                                <InputError message={errors.purpose} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="checked_in_at" value="Waktu Check-In *" />
                                <TextInput
                                    id="checked_in_at"
                                    type="datetime-local"
                                    value={data.checked_in_at}
                                    onChange={(e) => setData('checked_in_at', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError message={errors.checked_in_at} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="notes" value="Catatan" />
                                <textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="mt-1 block w-full border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm"
                                    rows={3}
                                    placeholder="Catatan tambahan"
                                />
                                <InputError message={errors.notes} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                            <Link
                                href={route('security.visitors.index')}
                                className="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                ← Batal
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {visitor ? 'Perbarui' : 'Check-In'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
