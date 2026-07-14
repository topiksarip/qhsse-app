import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

interface Certificate {
    id: number;
    certificate_type: string;
    certificate_number: string;
    issued_date: string | null;
    expiry_date: string | null;
    issuing_body: string | null;
    notes: string | null;
}

interface Asset {
    id: number;
    asset_number: string;
    name: string;
}

export default function CreateOrEdit({ asset, certificate }: PageProps<{
    asset: Asset;
    certificate?: Certificate;
}>) {
    const isEditing = !!certificate;

    const { data, setData, post, put, processing, errors } = useForm({
        certificate_type: certificate?.certificate_type || '',
        certificate_number: certificate?.certificate_number || '',
        issued_date: certificate?.issued_date || '',
        expiry_date: certificate?.expiry_date || '',
        issuing_body: certificate?.issuing_body || '',
        notes: certificate?.notes || '',
        certificate_file: null as File | null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEditing) {
            post(`/assets/${asset.id}/certificates/${certificate.id}?_method=PUT`);
        } else {
            post(`/assets/${asset.id}/certificates`);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link
                        href={`/assets/${asset.id}/certificates`}
                        className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block"
                    >
                        ← Back to Certificates
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        {isEditing ? 'Edit Certificate' : 'Add Certificate'} - {asset.asset_number}
                    </h2>
                </div>
            }
        >
            <Head title={isEditing ? 'Edit Certificate' : 'Add Certificate'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Certificate Type */}
                                <div>
                                    <InputLabel htmlFor="certificate_type" value="Certificate Type *" />
                                    <TextInput
                                        id="certificate_type"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.certificate_type}
                                        onChange={(e) => setData('certificate_type', e.target.value)}
                                        placeholder="Contoh: Kalibrasi, SIO, Sertifikat Kelayakan"
                                        required
                                    />
                                    <InputError message={errors.certificate_type} className="mt-2" />
                                </div>

                                {/* Certificate Number */}
                                <div>
                                    <InputLabel htmlFor="certificate_number" value="Certificate Number *" />
                                    <TextInput
                                        id="certificate_number"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.certificate_number}
                                        onChange={(e) => setData('certificate_number', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.certificate_number} className="mt-2" />
                                </div>

                                {/* Issued Date */}
                                <div>
                                    <InputLabel htmlFor="issued_date" value="Tanggal Terbit *" />
                                    <TextInput
                                        id="issued_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.issued_date}
                                        onChange={(e) => setData('issued_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.issued_date} className="mt-2" />
                                </div>

                                {/* Expiry Date */}
                                <div>
                                    <InputLabel htmlFor="expiry_date" value="Tanggal Kedaluwarsa" />
                                    <TextInput
                                        id="expiry_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.expiry_date}
                                        onChange={(e) => setData('expiry_date', e.target.value)}
                                    />
                                    <InputError message={errors.expiry_date} className="mt-2" />
                                    <p className="mt-1 text-xs text-gray-500">
                                        Status dihitung otomatis dari tanggal kedaluwarsa.
                                    </p>
                                </div>

                                {/* Issuing Authority */}
                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="issuing_body" value="Lembaga Penerbit" />
                                    <TextInput
                                        id="issuing_body"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.issuing_body}
                                        onChange={(e) => setData('issuing_body', e.target.value)}
                                    />
                                    <InputError message={errors.issuing_body} className="mt-2" />
                                </div>
                            </div>

                            {/* Notes */}
                            <div className="mt-6">
                                <InputLabel htmlFor="notes" value="Notes" />
                                <textarea
                                    id="notes"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} className="mt-2" />
                            </div>

                            {/* File Upload */}
                            <div className="mt-6">
                                <InputLabel htmlFor="certificate_file" value={isEditing ? 'Ganti Bukti Sertifikat' : 'Bukti Sertifikat'} />
                                <input
                                    id="certificate_file"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('certificate_file', e.target.files?.[0] ?? null)}
                                />
                                <InputError message={errors.certificate_file} className="mt-2" />
                                <p className="mt-1 text-xs text-gray-500">
                                    PDF, JPG, atau PNG; maksimum 10 MB. File disimpan secara privat.
                                </p>
                            </div>

                            {/* Submit Button */}
                            <div className="mt-6 flex items-center justify-end gap-4">
                                <Link
                                    href={`/assets/${asset.id}/certificates`}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {isEditing ? 'Update Certificate' : 'Add Certificate'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
