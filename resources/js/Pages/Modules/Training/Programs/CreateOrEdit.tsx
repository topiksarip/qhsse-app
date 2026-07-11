import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, TrainingProgram } from '@/types';
import { FormEventHandler } from 'react';

interface CreateOrEditProps extends PageProps {
    program?: TrainingProgram;
}

export default function CreateOrEdit({ auth, program }: CreateOrEditProps) {
    const isEdit = !!program;

    const { data, setData, post, put, processing, errors } = useForm({
        code: program?.code || '',
        name: program?.name || '',
        category: program?.category || '',
        duration_hours: program?.duration_hours || 1,
        description: program?.description || '',
        is_certification: program?.is_certification || false,
        validity_months: program?.validity_months || null,
        is_active: program?.is_active ?? true,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEdit && program) {
            put(route('training.programs.update', program.id));
        } else {
            post(route('training.programs.store'));
        }
    };

    const categories = [
        { value: 'safety', label: 'Safety' },
        { value: 'technical', label: 'Technical' },
        { value: 'compliance', label: 'Compliance' },
        { value: 'soft_skill', label: 'Soft Skill' },
        { value: 'environment', label: 'Environment' },
        { value: 'security', label: 'Security' },
        { value: 'quality', label: 'Quality' },
        { value: 'first_aid', label: 'First Aid' },
    ];

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {isEdit ? 'Edit Program Pelatihan' : 'Buat Program Pelatihan'}
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {isEdit ? 'Perbarui data program pelatihan' : 'Tambahkan program pelatihan baru ke sistem'}
                    </p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Program Pelatihan' : 'Buat Program Pelatihan'} />

            <div className="py-6">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Section: Informasi Program */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                INFORMASI PROGRAM
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
                                {/* Kode Program */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Kode Program <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value)}
                                        className={`w-full font-mono rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                            errors.code ? 'border-red-500' : ''
                                        }`}
                                        placeholder="HSE-IND"
                                        maxLength={50}
                                        required
                                    />
                                    {errors.code && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.code}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Kode unik program (max 50 karakter)
                                    </p>
                                </div>

                                {/* Nama Program */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nama Program <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={`w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                            errors.name ? 'border-red-500' : ''
                                        }`}
                                        placeholder="HSE Induction"
                                        maxLength={255}
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Nama program pelatihan
                                    </p>
                                </div>

                                {/* Kategori */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Kategori <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        className={`w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                            errors.category ? 'border-red-500' : ''
                                        }`}
                                        required
                                    >
                                        <option value="">— Pilih Kategori —</option>
                                        {categories.map(cat => (
                                            <option key={cat.value} value={cat.value}>{cat.label}</option>
                                        ))}
                                    </select>
                                    {errors.category && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.category}</p>
                                    )}
                                </div>

                                {/* Durasi (jam) */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Durasi (jam) <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        value={data.duration_hours}
                                        onChange={(e) => setData('duration_hours', parseInt(e.target.value))}
                                        className={`w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                            errors.duration_hours ? 'border-red-500' : ''
                                        }`}
                                        min={1}
                                        required
                                    />
                                    {errors.duration_hours && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.duration_hours}</p>
                                    )}
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Jumlah jam pelatihan
                                    </p>
                                </div>

                                {/* Deskripsi */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Deskripsi
                                    </label>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows={4}
                                        placeholder="Deskripsi program pelatihan..."
                                    />
                                    {errors.description && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Section: Sertifikasi */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                SERTIFIKASI
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
                                {/* Program Sertifikasi */}
                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_certification}
                                            onChange={(e) => {
                                                setData('is_certification', e.target.checked);
                                                if (!e.target.checked) {
                                                    setData('validity_months', null);
                                                }
                                            }}
                                            className="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Ya, program ini menerbitkan sertifikat
                                        </span>
                                    </label>
                                </div>

                                {/* Masa Berlaku (bulan) */}
                                {data.is_certification && (
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Masa Berlaku (bulan)
                                        </label>
                                        <input
                                            type="number"
                                            value={data.validity_months || ''}
                                            onChange={(e) => setData('validity_months', e.target.value ? parseInt(e.target.value) : null)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            min={1}
                                            placeholder="12"
                                        />
                                        {errors.validity_months && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.validity_months}</p>
                                        )}
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Jumlah bulan masa berlaku sertifikat. Kosongkan jika tidak ada masa berlaku.
                                        </p>
                                    </div>
                                )}

                                {/* Status Aktif */}
                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        />
                                        <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Program aktif
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <div className="flex items-center justify-end gap-3">
                                <Link
                                    href={route('training.programs.index')}
                                    className="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                                >
                                    Batal
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'Menyimpan...' : (isEdit ? 'Perbarui Program' : 'Simpan Program')}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
