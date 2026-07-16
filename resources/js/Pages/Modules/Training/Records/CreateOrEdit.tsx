import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, TrainingRecord, TrainingProgram, Employee } from '@/types';
import { FormEventHandler, useState } from 'react';
import ProgramSelect from '@/Components/Training/ProgramSelect';
import EmployeeSelect from '@/Components/Training/EmployeeSelect';
import CertificateUpload from '@/Components/Training/CertificateUpload';

interface CreateOrEditProps extends PageProps {
    record?: TrainingRecord;
    programs: TrainingProgram[];
    employees: Employee[];
    apd_items?: { id: number; item_number: string; catalog?: { name: string } }[];
    training_types?: Record<string, string>;
    fit_test_results?: Record<string, string>;
}

export default function CreateOrEdit({ auth, record, programs, employees, apd_items = [], training_types = {}, fit_test_results = {} }: CreateOrEditProps) {
    const isEdit = !!record;

    const { data, setData, post, put, processing, errors } = useForm({
        employee_id: record?.employee_id || '',
        training_program_id: record?.training_program_id || '',
        provider: record?.provider || '',
        start_date: record?.start_date || '',
        end_date: record?.end_date || '',
        status: record?.status || 'scheduled',
        score: record?.score || '',
        result: record?.result || '',
        certificate_number: record?.certificate_number || '',
        certificate_file: null as File | null,
        expiry_date: record?.expiry_date || '',
        notes: record?.notes || '',
        training_type: record?.training_type || '',
        apd_item_id: record?.apd_item_id || '',
        fit_test_result: record?.fit_test_result || '',
    });

    const [selectedProgram, setSelectedProgram] = useState<TrainingProgram | null>(
        record?.training_program || null
    );

    const handleProgramChange = (programId: string) => {
        setData('training_program_id', programId);
        const program = programs.find(p => p.id === parseInt(programId));
        setSelectedProgram(program || null);
        
        // Auto-calculate expiry if program has validity
        if (program?.is_certification && program.validity_months && data.end_date) {
            const endDate = new Date(data.end_date);
            endDate.setMonth(endDate.getMonth() + program.validity_months);
            setData('expiry_date', endDate.toISOString().split('T')[0]);
        }
    };

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEdit && record) {
            put(route('training.records.update', record.id));
        } else {
            post(route('training.records.store'));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {isEdit ? 'Edit Record Pelatihan' : 'Buat Record Pelatihan'}
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {isEdit ? 'Perbarui data record pelatihan' : 'Tambahkan record pelatihan baru ke sistem'}
                    </p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Record Pelatihan' : 'Buat Record Pelatihan'} />

            <div className="py-6">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Section: Informasi Pelatihan */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                INFORMASI PELATIHAN
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
                                {/* Karyawan */}
                                <div>
                                    <EmployeeSelect
                                        employees={employees}
                                        value={data.employee_id}
                                        onChange={(e) => setData('employee_id', e.target.value)}
                                        error={errors.employee_id}
                                        required
                                    />
                                </div>

                                {/* Program Pelatihan */}
                                <div>
                                    <ProgramSelect
                                        programs={programs}
                                        value={data.training_program_id}
                                        onChange={(e) => handleProgramChange(e.target.value)}
                                        error={errors.training_program_id}
                                        required
                                    />
                                    {selectedProgram && (
                                        <div className="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                            <div className="text-sm space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-gray-600 dark:text-gray-400">Kategori:</span>
                                                    <span className="font-medium text-gray-900 dark:text-gray-100">
                                                        {selectedProgram.category}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-gray-600 dark:text-gray-400">Durasi:</span>
                                                    <span className="font-medium text-gray-900 dark:text-gray-100">
                                                        {selectedProgram.duration_hours} jam
                                                    </span>
                                                </div>
                                                {selectedProgram.is_certification && (
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-gray-600 dark:text-gray-400">Sertifikasi:</span>
                                                        <span className="font-medium text-green-600 dark:text-green-400">
                                                            Ya (berlaku {selectedProgram.validity_months || 0} bulan)
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Provider */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Provider / Penyelenggara
                                    </label>
                                    <input
                                        type="text"
                                        value={data.provider}
                                        onChange={(e) => setData('provider', e.target.value)}
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Nama lembaga pelatihan"
                                    />
                                    {errors.provider && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.provider}</p>
                                    )}
                                </div>

                                {/* Tanggal Mulai & Selesai */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tanggal Mulai <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            className={`w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                                errors.start_date ? 'border-red-500' : ''
                                            }`}
                                            required
                                        />
                                        {errors.start_date && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.start_date}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Tanggal Selesai
                                        </label>
                                        <input
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) => {
                                                setData('end_date', e.target.value);
                                                // Auto-calculate expiry if program has validity
                                                if (selectedProgram?.is_certification && selectedProgram.validity_months && e.target.value) {
                                                    const endDate = new Date(e.target.value);
                                                    endDate.setMonth(endDate.getMonth() + selectedProgram.validity_months);
                                                    setData('expiry_date', endDate.toISOString().split('T')[0]);
                                                }
                                            }}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        {errors.end_date && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.end_date}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Status */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Status <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value as any)}
                                        className={`w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ${
                                            errors.status ? 'border-red-500' : ''
                                        }`}
                                        required
                                    >
                                        <option value="scheduled">Scheduled</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="expired">Expired</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    {errors.status && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.status}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Section: Hasil Pelatihan */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                HASIL PELATIHAN
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
                                {/* Score & Result */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Nilai / Score
                                        </label>
                                        <input
                                            type="number"
                                            value={data.score}
                                            onChange={(e) => setData('score', e.target.value ? parseFloat(e.target.value) : '')}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            min={0}
                                            max={100}
                                            step={0.1}
                                            placeholder="0-100"
                                        />
                                        {errors.score && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.score}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Hasil
                                        </label>
                                        <select
                                            value={data.result}
                                            onChange={(e) => setData('result', e.target.value as any)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">— Pilih Hasil —</option>
                                            <option value="pass">Pass</option>
                                            <option value="fail">Fail</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                        {errors.result && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.result}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Certificate Number & File */}
                                {selectedProgram?.is_certification && (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Nomor Sertifikat
                                            </label>
                                            <input
                                                type="text"
                                                value={data.certificate_number}
                                                onChange={(e) => setData('certificate_number', e.target.value)}
                                                className="w-full font-mono rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="CERT-2026-001"
                                            />
                                            {errors.certificate_number && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.certificate_number}</p>
                                            )}
                                        </div>

                                        <div>
                                            <CertificateUpload
                                                value={record?.certificate_file || null}
                                                onChange={(file) => setData('certificate_file', file)}
                                                onRemove={() => setData('certificate_file', null)}
                                                error={errors.certificate_file}
                                                label="File Sertifikat"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Tanggal Kedaluwarsa
                                            </label>
                                            <input
                                                type="date"
                                                value={data.expiry_date}
                                                onChange={(e) => setData('expiry_date', e.target.value)}
                                                className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            {errors.expiry_date && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.expiry_date}</p>
                                            )}
                                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Tanggal ini dihitung otomatis berdasarkan masa berlaku program
                                            </p>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Section: PPE Fit-Test */}
                        {(Object.keys(training_types).length > 0 || Object.keys(fit_test_results).length > 0) && (
                            <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    FIT-TEST APD & JENIS PELATIHAN
                                </h3>
                                <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Jenis Pelatihan
                                        </label>
                                        <select
                                            value={data.training_type}
                                            onChange={(e) => setData('training_type', e.target.value)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">— Pilih Jenis —</option>
                                            {Object.entries(training_types).map(([key, label]) => (
                                                <option key={key} value={key}>{label}</option>
                                            ))}
                                        </select>
                                        {errors.training_type && (
                                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.training_type}</p>
                                        )}
                                    </div>

                                    {data.training_type === 'ppe_fit_test' && (
                                        <>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Item APD (terkait fit-test)
                                                </label>
                                                <select
                                                    value={data.apd_item_id}
                                                    onChange={(e) => setData('apd_item_id', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option value="">— Pilih Item APD —</option>
                                                    {apd_items.map((item) => (
                                                        <option key={item.id} value={item.id}>
                                                            {item.item_number}
                                                        </option>
                                                    ))}
                                                </select>
                                                {errors.apd_item_id && (
                                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.apd_item_id}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Hasil Fit-Test
                                                </label>
                                                <select
                                                    value={data.fit_test_result}
                                                    onChange={(e) => setData('fit_test_result', e.target.value as any)}
                                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option value="">— Pilih Hasil —</option>
                                                    {Object.entries(fit_test_results).map(([key, label]) => (
                                                        <option key={key} value={key}>{label}</option>
                                                    ))}
                                                </select>
                                                {errors.fit_test_result && (
                                                    <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.fit_test_result}</p>
                                                )}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Section: Catatan */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                CATATAN
                            </h3>
                            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Catatan Tambahan
                                    </label>
                                    <textarea
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows={4}
                                        placeholder="Catatan mengenai pelatihan ini..."
                                    />
                                    {errors.notes && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.notes}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                            <div className="flex items-center justify-end gap-3">
                                <Link
                                    href={route('training.records.index')}
                                    className="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600"
                                >
                                    Batal
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'Menyimpan...' : (isEdit ? 'Perbarui Record' : 'Simpan Record')}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
