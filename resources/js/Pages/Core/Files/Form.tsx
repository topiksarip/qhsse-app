import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Form() {
    const { data, setData, post, processing, errors } = useForm({
        module_name: 'core.test',
        reference_id: '1',
        collection: 'default',
        file: null as File | null,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post(route('core.files.store'), { forceFormData: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Upload Managed File</h2>}>
            <Head title="Upload Managed File" />
            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div>
                            <InputLabel htmlFor="module_name" value="Module Name" />
                            <TextInput id="module_name" className="mt-1 block w-full" value={data.module_name} onChange={(event) => setData('module_name', event.target.value)} />
                            <InputError message={errors.module_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="reference_id" value="Reference ID" />
                            <TextInput id="reference_id" type="number" min="1" className="mt-1 block w-full" value={data.reference_id} onChange={(event) => setData('reference_id', event.target.value)} />
                            <InputError message={errors.reference_id} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="collection" value="Collection" />
                            <TextInput id="collection" className="mt-1 block w-full" value={data.collection} onChange={(event) => setData('collection', event.target.value)} />
                            <InputError message={errors.collection} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="file" value="File" />
                            <input id="file" type="file" className="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" onChange={(event) => setData('file', event.target.files?.[0] ?? null)} />
                            <p className="mt-1 text-xs text-gray-500">Allowed: jpg, jpeg, png, webp, pdf, doc, docx, xls, xlsx, csv, txt. Max 10 MB.</p>
                            <InputError message={errors.file} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-end gap-3">
                            <Link href={route('core.files.index')} className="text-sm text-gray-600 dark:text-gray-400">Cancel</Link>
                            <PrimaryButton disabled={processing}>Upload</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
