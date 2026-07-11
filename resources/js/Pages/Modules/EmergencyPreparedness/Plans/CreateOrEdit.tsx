import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { EmergencyPlan, Site, User, PageProps } from '@/types';

interface PlanFormProps extends PageProps {
    plan?: EmergencyPlan & {
        emergency_contacts?: Array<{ name: string; role: string; phone: string }>;
    };
    sites: Site[];
    users: User[];
}

interface EmergencyContactInput {
    name: string;
    role: string;
    phone: string;
}

export default function CreateOrEdit({ auth, plan, sites, users }: PlanFormProps) {
    const [emergencyContacts, setEmergencyContacts] = useState<EmergencyContactInput[]>(
        plan?.emergency_contacts || []
    );

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        type: plan?.type || '',
        site_id: plan?.site_id?.toString() || '',
        contact_person_id: plan?.contact_person_id?.toString() || '',
        description: plan?.description || '',
        response_procedure: plan?.response_procedure || '',
        escalation_procedure: plan?.escalation_procedure || '',
        equipment_needed: plan?.equipment_needed || '',
        emergency_contacts: plan?.emergency_contacts || [],
    });

    const addContact = () => {
        const newContacts = [...emergencyContacts, { name: '', role: '', phone: '' }];
        setEmergencyContacts(newContacts);
        setData('emergency_contacts', newContacts);
    };

    const removeContact = (index: number) => {
        const newContacts = emergencyContacts.filter((_, i) => i !== index);
        setEmergencyContacts(newContacts);
        setData('emergency_contacts', newContacts);
    };

    const updateContact = (index: number, field: keyof EmergencyContactInput, value: string) => {
        const newContacts = [...emergencyContacts];
        newContacts[index][field] = value;
        setEmergencyContacts(newContacts);
        setData('emergency_contacts', newContacts);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (plan) {
            put(route('emergency.plans.update', plan.id));
        } else {
            post(route('emergency.plans.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {plan ? 'Edit Rencana Darurat' : 'Buat Rencana Darurat'}
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {plan ? 'Perbarui informasi rencana darurat' : 'Isi data rencana darurat dengan lengkap'}
                    </p>
                </div>
            }
        >
            <Head title={plan ? 'Edit Rencana Darurat' : 'Buat Rencana Darurat'} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* Section: Informasi Umum */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 space-y-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    INFORMASI UMUM
                                </h3>

                                <div>
                                    <InputLabel htmlFor="plan_number" value="Nomor Rencana" />
                                    <TextInput
                                        id="plan_number"
                                        type="text"
                                        value={plan?.plan_number || 'Auto-generated'}
                                        className="mt-1 block w-full bg-gray-100 dark:bg-gray-900"
                                        disabled
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Nomor akan dibuat otomatis saat simpan
                                    </p>
                                </div>

                                <div>
                                    <InputLabel htmlFor="name" value="Nama *" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        name="name"
                                        value={data.name}
                                        className="mt-1 block w-full"
                                        placeholder="Masukkan nama rencana darurat..."
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="type" value="Tipe *" />
                                    <select
                                        id="type"
                                        name="type"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">— Pilih Tipe —</option>
                                        <option value="fire">Kebakaran</option>
                                        <option value="medical">Medis</option>
                                        <option value="spill">Tumpahan</option>
                                        <option value="evacuation">Evakuasi</option>
                                        <option value="natural_disaster">Bencana Alam</option>
                                        <option value="security">Keamanan</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    <InputError message={errors.type} className="mt-2" />
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

                                <div>
                                    <InputLabel htmlFor="contact_person_id" value="Kontak Person *" />
                                    <select
                                        id="contact_person_id"
                                        name="contact_person_id"
                                        value={data.contact_person_id}
                                        onChange={(e) => setData('contact_person_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">— Pilih User —</option>
                                        {users.map((user) => (
                                            <option key={user.id} value={user.id}>
                                                {user.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.contact_person_id} className="mt-2" />
                                </div>
                            </div>
                        </div>

                        {/* Section: Deskripsi & Prosedur */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 space-y-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    DESKRIPSI & PROSEDUR
                                </h3>

                                <div>
                                    <InputLabel htmlFor="description" value="Deskripsi *" />
                                    <textarea
                                        id="description"
                                        name="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={4}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="Jelaskan rencana darurat secara detail..."
                                        required
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="response_procedure" value="Prosedur Respons *" />
                                    <textarea
                                        id="response_procedure"
                                        name="response_procedure"
                                        value={data.response_procedure}
                                        onChange={(e) => setData('response_procedure', e.target.value)}
                                        rows={6}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="Langkah-langkah respons darurat:&#10;1. ...&#10;2. ..."
                                        required
                                    />
                                    <InputError message={errors.response_procedure} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="escalation_procedure" value="Prosedur Eskalasi *" />
                                    <textarea
                                        id="escalation_procedure"
                                        name="escalation_procedure"
                                        value={data.escalation_procedure}
                                        onChange={(e) => setData('escalation_procedure', e.target.value)}
                                        rows={6}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="Prosedur eskalasi:&#10;1. Hubungi...&#10;2. Eskalasi ke..."
                                        required
                                    />
                                    <InputError message={errors.escalation_procedure} className="mt-2" />
                                </div>
                            </div>
                        </div>

                        {/* Section: Kontak Darurat Tambahan */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 space-y-4">
                                <div className="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        KONTAK DARURAT TAMBAHAN
                                    </h3>
                                    <button
                                        type="button"
                                        onClick={addContact}
                                        className="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        + Tambah Kontak
                                    </button>
                                </div>

                                {emergencyContacts.length === 0 ? (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada kontak tambahan. Klik tombol di atas untuk menambah.
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {emergencyContacts.map((contact, index) => (
                                            <div key={index} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <InputLabel htmlFor={`contact_name_${index}`} value="Nama" />
                                                        <TextInput
                                                            id={`contact_name_${index}`}
                                                            type="text"
                                                            value={contact.name}
                                                            onChange={(e) => updateContact(index, 'name', e.target.value)}
                                                            className="mt-1 block w-full"
                                                        />
                                                    </div>
                                                    <div>
                                                        <InputLabel htmlFor={`contact_role_${index}`} value="Peran" />
                                                        <TextInput
                                                            id={`contact_role_${index}`}
                                                            type="text"
                                                            value={contact.role}
                                                            onChange={(e) => updateContact(index, 'role', e.target.value)}
                                                            className="mt-1 block w-full"
                                                        />
                                                    </div>
                                                    <div>
                                                        <InputLabel htmlFor={`contact_phone_${index}`} value="Telepon" />
                                                        <div className="flex gap-2">
                                                            <TextInput
                                                                id={`contact_phone_${index}`}
                                                                type="text"
                                                                value={contact.phone}
                                                                onChange={(e) => updateContact(index, 'phone', e.target.value)}
                                                                className="mt-1 block w-full"
                                                            />
                                                            <button
                                                                type="button"
                                                                onClick={() => removeContact(index)}
                                                                className="mt-1 px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                                            >
                                                                🗑
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Section: Peralatan */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    PERALATAN
                                </h3>

                                <div>
                                    <InputLabel htmlFor="equipment_needed" value="Peralatan yang Dibutuhkan" />
                                    <textarea
                                        id="equipment_needed"
                                        name="equipment_needed"
                                        value={data.equipment_needed}
                                        onChange={(e) => setData('equipment_needed', e.target.value)}
                                        rows={4}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="APAR, Hydrant, Eye Wash, Spill Kit, Stretcher, Radio Komunikasi..."
                                    />
                                    <InputError message={errors.equipment_needed} className="mt-2" />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Opsional</p>
                                </div>
                            </div>
                        </div>

                        {/* Action Bar */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 flex items-center justify-between">
                                <Link
                                    href={route('emergency.plans.index')}
                                    className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    ← Batal
                                </Link>

                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
