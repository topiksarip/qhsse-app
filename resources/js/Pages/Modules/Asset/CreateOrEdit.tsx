import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler } from 'react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

interface Asset {
    id: number;
    asset_number: string;
    name: string;
    category: string;
    serial_number: string | null;
    model: string | null;
    manufacturer: string | null;
    status: string;
    safety_critical: boolean;
    purchase_date: string | null;
    installation_date: string | null;
    warranty_expiry_date: string | null;
    next_inspection_date: string | null;
    site_id: number;
    area_id: number | null;
    department_id: number | null;
    description: string | null;
    notes: string | null;
}

export default function CreateOrEdit({ asset, sites, areas, departments, categories }: PageProps<{
    asset?: Asset;
    sites: Array<{ id: number; name: string }>;
    areas: Array<{ id: number; name: string; site_id: number }>;
    departments: Array<{ id: number; name: string; site_id: number }>;
    categories: Record<string, string>;
}>) {
    const isEditing = !!asset;

    const { data, setData, post, put, processing, errors } = useForm({
        name: asset?.name || '',
        category: asset?.category || '',
        serial_number: asset?.serial_number || '',
        model: asset?.model || '',
        manufacturer: asset?.manufacturer || '',
        safety_critical: asset?.safety_critical || false,
        purchase_date: asset?.purchase_date || '',
        installation_date: asset?.installation_date || '',
        warranty_expiry_date: asset?.warranty_expiry_date || '',
        next_inspection_date: asset?.next_inspection_date || '',
        site_id: asset?.site_id || '',
        area_id: asset?.area_id || '',
        department_id: asset?.department_id || '',
        description: asset?.description || '',
        notes: asset?.notes || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEditing) {
            put(`/assets/${asset.id}`);
        } else {
            post('/assets');
        }
    };

    const filteredAreas = areas.filter(area => area.site_id === Number(data.site_id));
    const filteredDepartments = departments.filter(department => department.site_id === Number(data.site_id));

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/assets" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block">
                        ← Back to Assets
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        {isEditing ? 'Edit Asset' : 'Create New Asset'}
                    </h2>
                </div>
            }
        >
            <Head title={isEditing ? 'Edit Asset' : 'Create Asset'} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Asset Number */}
                                <div>
                                    <InputLabel htmlFor="asset_number" value="Asset Number" />
                                    <p id="asset_number" className="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                        {asset?.asset_number ?? 'Dibuat otomatis oleh Numbering Service setelah disimpan'}
                                    </p>
                                </div>

                                {/* Name */}
                                <div>
                                    <InputLabel htmlFor="name" value="Asset Name *" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                {/* Category */}
                                <div>
                                    <InputLabel htmlFor="category" value="Category *" />
                                    <select
                                        id="category"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        required
                                    >
                                        <option value="">Select Category</option>
                                        {Object.entries(categories).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.category} className="mt-2" />
                                </div>

                                {/* Serial Number */}
                                <div>
                                    <InputLabel htmlFor="serial_number" value="Serial Number" />
                                    <TextInput
                                        id="serial_number"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.serial_number}
                                        onChange={(e) => setData('serial_number', e.target.value)}
                                    />
                                    <InputError message={errors.serial_number} className="mt-2" />
                                </div>

                                {/* Model */}
                                <div>
                                    <InputLabel htmlFor="model" value="Model" />
                                    <TextInput
                                        id="model"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                    />
                                    <InputError message={errors.model} className="mt-2" />
                                </div>

                                {/* Manufacturer */}
                                <div>
                                    <InputLabel htmlFor="manufacturer" value="Manufacturer" />
                                    <TextInput
                                        id="manufacturer"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.manufacturer}
                                        onChange={(e) => setData('manufacturer', e.target.value)}
                                    />
                                    <InputError message={errors.manufacturer} className="mt-2" />
                                </div>


                                {/* Safety Critical */}
                                <div className="flex items-center">
                                    <input
                                        id="safety_critical"
                                        type="checkbox"
                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                        checked={data.safety_critical}
                                        onChange={(e) => setData('safety_critical', e.target.checked)}
                                    />
                                    <label htmlFor="safety_critical" className="ml-2 text-sm text-gray-700">
                                        Safety Critical Asset
                                    </label>
                                    <InputError message={errors.safety_critical} className="mt-2" />
                                </div>

                                {/* Site */}
                                <div>
                                    <InputLabel htmlFor="site_id" value="Site *" />
                                    <select
                                        id="site_id"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        value={data.site_id}
                                        onChange={(e) => {
                                            setData('site_id', e.target.value);
                                            setData('area_id', '');
                                            setData('department_id', '');
                                        }}
                                        required
                                    >
                                        <option value="">Select Site</option>
                                        {sites.map(site => (
                                            <option key={site.id} value={site.id}>{site.name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.site_id} className="mt-2" />
                                </div>

                                {/* Area */}
                                <div>
                                    <InputLabel htmlFor="area_id" value="Area" />
                                    <select
                                        id="area_id"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        value={data.area_id}
                                        onChange={(e) => setData('area_id', e.target.value)}
                                        disabled={!data.site_id}
                                    >
                                        <option value="">Select Area</option>
                                        {filteredAreas.map(area => (
                                            <option key={area.id} value={area.id}>{area.name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.area_id} className="mt-2" />
                                </div>

                                {/* Department */}
                                <div>
                                    <InputLabel htmlFor="department_id" value="Department" />
                                    <select
                                        id="department_id"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        value={data.department_id}
                                        onChange={(e) => setData('department_id', e.target.value)}
                                    >
                                        <option value="">Select Department</option>
                                        {filteredDepartments.map(dept => (
                                            <option key={dept.id} value={dept.id}>{dept.name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.department_id} className="mt-2" />
                                </div>

                                {/* Purchase Date */}
                                <div>
                                    <InputLabel htmlFor="purchase_date" value="Purchase Date" />
                                    <TextInput
                                        id="purchase_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.purchase_date}
                                        onChange={(e) => setData('purchase_date', e.target.value)}
                                    />
                                    <InputError message={errors.purchase_date} className="mt-2" />
                                </div>

                                {/* Installation Date */}
                                <div>
                                    <InputLabel htmlFor="installation_date" value="Installation Date" />
                                    <TextInput
                                        id="installation_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.installation_date}
                                        onChange={(e) => setData('installation_date', e.target.value)}
                                    />
                                    <InputError message={errors.installation_date} className="mt-2" />
                                </div>

                                {/* Warranty Expiry Date */}
                                <div>
                                    <InputLabel htmlFor="warranty_expiry_date" value="Warranty Expiry Date" />
                                    <TextInput
                                        id="warranty_expiry_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.warranty_expiry_date}
                                        onChange={(e) => setData('warranty_expiry_date', e.target.value)}
                                    />
                                    <InputError message={errors.warranty_expiry_date} className="mt-2" />
                                </div>

                                {/* Next Inspection Date */}
                                <div>
                                    <InputLabel htmlFor="next_inspection_date" value="Next Inspection Date" />
                                    <TextInput
                                        id="next_inspection_date"
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.next_inspection_date}
                                        onChange={(e) => setData('next_inspection_date', e.target.value)}
                                    />
                                    <InputError message={errors.next_inspection_date} className="mt-2" />
                                </div>
                            </div>

                            {/* Description */}
                            <div className="mt-6">
                                <InputLabel htmlFor="description" value="Description" />
                                <textarea
                                    id="description"
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    rows={3}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} className="mt-2" />
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

                            {/* Submit Button */}
                            <div className="mt-6 flex items-center justify-end gap-4">
                                <Link
                                    href="/assets"
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {isEditing ? 'Update Asset' : 'Create Asset'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
