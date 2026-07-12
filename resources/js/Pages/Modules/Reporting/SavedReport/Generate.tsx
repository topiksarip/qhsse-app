import React, { useState, useEffect } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ReportTemplate } from '@/types/modules/reporting';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

interface Site {
    id: number;
    name: string;
    code: string;
}

interface Department {
    id: number;
    name: string;
    code: string;
}

interface Props {
    templates: ReportTemplate[];
    sites: Site[];
    departments: Department[];
    selectedTemplate?: ReportTemplate;
}

export default function Generate({ templates, sites, departments, selectedTemplate }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        template_id: selectedTemplate?.id?.toString() || '',
        name: '',
        date_from: '',
        date_to: '',
        site_id: '',
        department_id: '',
        format: 'pdf',
        include_charts: true,
    });

    const [selectedTemplateData, setSelectedTemplateData] = useState<ReportTemplate | undefined>(selectedTemplate);

    useEffect(() => {
        if (data.template_id) {
            const template = templates.find(t => t.id === Number(data.template_id));
            setSelectedTemplateData(template);
            
            // Auto-generate name
            if (template && !data.name) {
                const today = new Date().toISOString().split('T')[0];
                setData('name', `${template.name} - ${today}`);
            }
        }
    }, [data.template_id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('saved-reports.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Generate Report" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Generate Report</h1>
                        <p className="text-sm text-gray-600 mt-1">Buat laporan QHSSE baru</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Template Selection */}
                        <div className="bg-white shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Pilih Template</h3>
                            
                            <div className="space-y-4">
                                <div>
                                    <InputLabel htmlFor="template_id" value="Template *" />
                                    <select
                                        id="template_id"
                                        value={data.template_id}
                                        onChange={(e) => setData('template_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">-- Pilih Template --</option>
                                        {templates.map((template) => (
                                            <option key={template.id} value={template.id}>
                                                {template.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.template_id} className="mt-2" />
                                </div>

                                {selectedTemplateData && (
                                    <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                        <p className="text-sm text-blue-900">{selectedTemplateData.description}</p>
                                    </div>
                                )}

                                <div>
                                    <InputLabel htmlFor="name" value="Nama Laporan *" />
                                    <TextInput
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Contoh: Laporan Insiden Januari 2026"
                                        className="mt-1 block w-full"
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>
                            </div>
                        </div>

                        {/* Parameters */}
                        <div className="bg-white shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Parameter Laporan</h3>
                            
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="date_from" value="Tanggal Mulai *" />
                                        <TextInput
                                            id="date_from"
                                            type="date"
                                            value={data.date_from}
                                            onChange={(e) => setData('date_from', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                        <InputError message={errors.date_from} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="date_to" value="Tanggal Akhir *" />
                                        <TextInput
                                            id="date_to"
                                            type="date"
                                            value={data.date_to}
                                            onChange={(e) => setData('date_to', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                        <InputError message={errors.date_to} className="mt-2" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="site_id" value="Site (Opsional)" />
                                        <select
                                            id="site_id"
                                            value={data.site_id}
                                            onChange={(e) => setData('site_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        >
                                            <option value="">-- Semua Site --</option>
                                            {sites.map((site) => (
                                                <option key={site.id} value={site.id}>
                                                    {site.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.site_id} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="department_id" value="Departemen (Opsional)" />
                                        <select
                                            id="department_id"
                                            value={data.department_id}
                                            onChange={(e) => setData('department_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        >
                                            <option value="">-- Semua Departemen --</option>
                                            {departments.map((dept) => (
                                                <option key={dept.id} value={dept.id}>
                                                    {dept.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.department_id} className="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Output Format */}
                        <div className="bg-white shadow rounded-lg p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Format Output</h3>
                            
                            <div className="space-y-4">
                                <div>
                                    <InputLabel htmlFor="format" value="Format File *" />
                                    <div className="grid grid-cols-3 gap-4 mt-2">
                                        {['csv', 'pdf', 'excel'].map((format) => (
                                            <label
                                                key={format}
                                                className={`flex items-center justify-center border-2 rounded-lg p-4 cursor-pointer transition-all ${
                                                    data.format === format
                                                        ? 'border-blue-600 bg-blue-50'
                                                        : 'border-gray-200 hover:border-gray-300'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="format"
                                                    value={format}
                                                    checked={data.format === format}
                                                    onChange={(e) => setData('format', e.target.value)}
                                                    className="sr-only"
                                                />
                                                <span className="font-medium text-lg">{format.toUpperCase()}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.format} className="mt-2" />
                                </div>

                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="include_charts"
                                        checked={data.include_charts}
                                        onChange={(e) => setData('include_charts', e.target.checked)}
                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    />
                                    <InputLabel htmlFor="include_charts" value="Sertakan grafik (untuk PDF dan Excel)" className="ml-2 cursor-pointer" />
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex justify-end gap-3">
                            <SecondaryButton
                                type="button"
                                onClick={() => router.visit(route('saved-reports.index'))}
                            >
                                Batal
                            </SecondaryButton>
                            <PrimaryButton type="submit" disabled={processing}>
                                {processing ? 'Memproses...' : 'Generate Laporan'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
