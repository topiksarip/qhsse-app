import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ReportSection, ReportTemplate } from '@/types/modules/reporting';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type SectionForm = Required<Pick<ReportSection, 'key' | 'label' | 'enabled'>> & {
    data_source: string;
};

interface Props {
    template: ReportTemplate | null;
}

const emptySection = (): SectionForm => ({ key: '', label: '', enabled: true, data_source: '' });

export default function CreateOrEdit({ template }: Props) {
    const isEdit = template !== null;
    const isPredefined = template?.is_predefined ?? false;
    const sections: SectionForm[] = (template?.config?.sections ?? []).map((section) => ({
        key: section.key,
        label: section.label,
        enabled: section.enabled,
        data_source: section.data_source ?? '',
    }));

    const { data, setData, post, put, processing, errors } = useForm({
        name: template?.name ?? '',
        type: 'custom',
        description: template?.description ?? '',
        is_active: template?.is_active ?? true,
        config: {
            sections: sections.length > 0 ? sections : [emptySection()],
            default_parameters: template?.config?.default_parameters ?? {},
        },
    });

    const updateSection = <K extends keyof SectionForm>(index: number, field: K, value: SectionForm[K]) => {
        const next = [...data.config.sections];
        next[index] = { ...next[index], [field]: value };
        setData('config', { ...data.config, sections: next });
    };

    const addSection = () => setData('config', {
        ...data.config,
        sections: [...data.config.sections, emptySection()],
    });

    const removeSection = (index: number) => setData('config', {
        ...data.config,
        sections: data.config.sections.filter((_, itemIndex) => itemIndex !== index),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (isEdit && template) {
            put(route('report-templates.update', template.id));
            return;
        }
        post(route('report-templates.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? 'Edit Template Laporan' : 'Buat Template Laporan'} />

            <div className="py-6">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {isEdit ? 'Edit Template Laporan' : 'Buat Template Laporan'}
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Template custom menentukan section dan sumber data laporan QHSSE.
                        </p>
                    </div>

                    {isPredefined && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
                            Template bawaan hanya dapat mengubah deskripsi dan status aktif.
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        <section className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Informasi Template</h2>
                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="name" value="Nama Template *" />
                                    <TextInput
                                        id="name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                        disabled={isPredefined}
                                        className="mt-1 block w-full disabled:bg-gray-100 dark:disabled:bg-gray-700"
                                        required={!isPredefined}
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="type" value="Tipe" />
                                    <TextInput id="type" value={isPredefined ? template?.type ?? '' : 'Custom'} disabled className="mt-1 block w-full bg-gray-100 dark:bg-gray-700" />
                                </div>
                                <div className="flex items-end pb-2">
                                    <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(event) => setData('is_active', event.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Template aktif
                                    </label>
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="description" value="Deskripsi" />
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(event) => setData('description', event.target.value)}
                                        rows={4}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>
                            </div>
                        </section>

                        {!isPredefined && (
                            <section className="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Section Laporan</h2>
                                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan key stabil tanpa spasi, misalnya incident_summary.</p>
                                    </div>
                                    <SecondaryButton type="button" onClick={addSection}>+ Section</SecondaryButton>
                                </div>

                                <div className="mt-5 space-y-4">
                                    {data.config.sections.map((section, index) => (
                                        <div key={index} className="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <InputLabel htmlFor={`section-key-${index}`} value="Key *" />
                                                    <TextInput
                                                        id={`section-key-${index}`}
                                                        value={section.key}
                                                        onChange={(event) => updateSection(index, 'key', event.target.value)}
                                                        className="mt-1 block w-full"
                                                        required
                                                    />
                                                    <InputError message={(errors as Record<string, string>)[`config.sections.${index}.key`]} className="mt-2" />
                                                </div>
                                                <div>
                                                    <InputLabel htmlFor={`section-label-${index}`} value="Label *" />
                                                    <TextInput
                                                        id={`section-label-${index}`}
                                                        value={section.label}
                                                        onChange={(event) => updateSection(index, 'label', event.target.value)}
                                                        className="mt-1 block w-full"
                                                        required
                                                    />
                                                    <InputError message={(errors as Record<string, string>)[`config.sections.${index}.label`]} className="mt-2" />
                                                </div>
                                                <div>
                                                    <InputLabel htmlFor={`section-source-${index}`} value="Sumber Data" />
                                                    <TextInput
                                                        id={`section-source-${index}`}
                                                        value={section.data_source}
                                                        onChange={(event) => updateSection(index, 'data_source', event.target.value)}
                                                        className="mt-1 block w-full"
                                                        placeholder="incidents"
                                                    />
                                                </div>
                                                <div className="flex items-end justify-between gap-4 pb-2">
                                                    <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                        <input
                                                            type="checkbox"
                                                            checked={section.enabled}
                                                            onChange={(event) => updateSection(index, 'enabled', event.target.checked)}
                                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                        />
                                                        Aktif
                                                    </label>
                                                    {data.config.sections.length > 1 && (
                                                        <button type="button" onClick={() => removeSection(index)} className="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400">
                                                            Hapus
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        <div className="flex justify-end gap-3">
                            <SecondaryButton type="button" onClick={() => router.visit(route('report-templates.index'))}>Batal</SecondaryButton>
                            <PrimaryButton type="submit" disabled={processing}>{processing ? 'Menyimpan...' : 'Simpan Template'}</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
