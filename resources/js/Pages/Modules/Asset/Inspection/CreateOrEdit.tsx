import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

interface Inspection {
    id: number;
    inspection_date: string;
    result: string;
    findings: string | null;
    recommendations: string | null;
    notes: string | null;
}

interface Asset {
    id: number;
    asset_number: string;
    name: string;
}

export default function CreateOrEdit({ auth, asset, inspection, results }: PageProps<{
    asset: Asset;
    inspection?: Inspection;
    results: Record<string, string>;
}>) {
    const isEditing = !!inspection;

    const { data, setData, post, put, processing, errors } = useForm({
        inspection_date: inspection?.inspection_date || new Date().toISOString().split('T')[0],
        result: inspection?.result || '',
        findings: inspection?.findings || '',
        recommendations: inspection?.recommendations || '',
        notes: inspection?.notes || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEditing) {
            put(`/assets/${asset.id}/inspections/${inspection.id}`);
        } else {
            post(`/assets/${asset.id}/inspections`);
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link
                        href={`/assets/${asset.id}/inspections`}
                        className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block"
                    >
                        ← Back to Inspections
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        {isEditing ? 'Edit Inspection' : 'Record Inspection'} - {asset.asset_number}
                    </h2>
                </div>
            }
        >
            <Head title={isEditing ? 'Edit Inspection' : 'Record Inspection'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Inspection Date */}
                                <div>
                                    <InputLabel htmlFor="inspection_date" value="Inspection Date *" />
                                    <TextInput
                                        id="inspection_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.inspection_date}
                                        onChange={(e) => setData('inspection_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.inspection_date} className="mt-2" />
                                </div>

                                {/* Result */}
                                <div>
                                    <InputLabel htmlFor="result" value="Inspection Result *" />
                                    <select
                                        id="result"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        value={data.result}
                                        onChange={(e) => setData('result', e.target.value)}
                                        required
                                    >
                                        <option value="">Select Result</option>
                                        {Object.entries(results).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.result} className="mt-2" />
                                </div>
                            </div>

                            {/* Findings */}
                            <div className="mt-6">
                                <InputLabel htmlFor="findings" value="Findings" />
                                <textarea
                                    id="findings"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    rows={4}
                                    value={data.findings}
                                    onChange={(e) => setData('findings', e.target.value)}
                                    placeholder="Describe inspection findings..."
                                />
                                <InputError message={errors.findings} className="mt-2" />
                            </div>

                            {/* Recommendations */}
                            <div className="mt-6">
                                <InputLabel htmlFor="recommendations" value="Recommendations" />
                                <textarea
                                    id="recommendations"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    rows={4}
                                    value={data.recommendations}
                                    onChange={(e) => setData('recommendations', e.target.value)}
                                    placeholder="Recommendations for maintenance or corrective actions..."
                                />
                                <InputError message={errors.recommendations} className="mt-2" />
                            </div>

                            {/* Notes */}
                            <div className="mt-6">
                                <InputLabel htmlFor="notes" value="Additional Notes" />
                                <textarea
                                    id="notes"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} className="mt-2" />
                            </div>

                            {/* Info Box for Failed Inspections */}
                            {data.result === 'fail' && (
                                <div className="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-md">
                                    <p className="text-sm text-orange-800">
                                        <strong>Note:</strong> Failed inspections can be linked to CAPA actions after creation
                                        to track corrective measures.
                                    </p>
                                </div>
                            )}

                            {/* Submit Button */}
                            <div className="mt-6 flex items-center justify-end gap-4">
                                <Link
                                    href={`/assets/${asset.id}/inspections`}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {isEditing ? 'Update Inspection' : 'Record Inspection'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
