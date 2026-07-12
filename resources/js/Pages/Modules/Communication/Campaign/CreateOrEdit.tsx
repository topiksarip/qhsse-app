import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

interface Campaign {
    id: number;
    campaign_number: string;
    title: string;
    type: string;
    status: string;
    target_audience: string;
    description?: string;
    content?: string;
    published_at: string | null;
    expires_at: string | null;
}

interface Props {
    campaign?: Campaign;
}

export default function CreateOrEdit({ campaign }: Props) {
    const isEdit = !!campaign;

    const { data, setData, post, put, processing, errors } = useForm({
        title: campaign?.title || '',
        type: campaign?.type || 'general_announcement',
        target_audience: campaign?.target_audience || 'all_employees',
        description: campaign?.description || '',
        content: campaign?.content || '',
        expires_at: campaign?.expires_at || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (isEdit) {
            put(route('campaigns.update', campaign.id), {
                preserveScroll: true,
            });
        } else {
            post(route('campaigns.store'), {
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={isEdit ? `Edit Campaign: ${campaign.title}` : 'Buat Campaign Baru'} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                {isEdit ? `Edit Campaign: ${campaign.title}` : 'Buat Campaign Baru'}
                            </h1>
                            {isEdit && (
                                <p className="text-sm text-gray-600 mt-1">{campaign.campaign_number}</p>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={() => router.visit(route('campaigns.index'))}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Batal
                            </button>
                        </div>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="bg-white shadow rounded-lg p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Informasi Campaign</h2>

                            <div className="space-y-4">
                                {/* Title */}
                                <div>
                                    <InputLabel htmlFor="title" value="Judul Campaign *" />
                                    <TextInput
                                        id="title"
                                        type="text"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        className="mt-1 block w-full"
                                        required
                                    />
                                    <InputError message={errors.title} className="mt-2" />
                                </div>

                                {/* Type */}
                                <div>
                                    <InputLabel htmlFor="type" value="Tipe Campaign *" />
                                    <select
                                        id="type"
                                        value={data.type}
                                        onChange={(e) => setData('type', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="safety_alert">Safety Alert</option>
                                        <option value="policy_update">Policy Update</option>
                                        <option value="training_announcement">Training Announcement</option>
                                        <option value="general_announcement">General Announcement</option>
                                    </select>
                                    <InputError message={errors.type} className="mt-2" />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Pilih tipe yang sesuai untuk kategori campaign
                                    </p>
                                </div>

                                {/* Target Audience */}
                                <div>
                                    <InputLabel htmlFor="target_audience" value="Target Audience *" />
                                    <select
                                        id="target_audience"
                                        value={data.target_audience}
                                        onChange={(e) => setData('target_audience', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="all_employees">Semua Karyawan</option>
                                        <option value="management">Management</option>
                                        <option value="supervisors">Supervisors</option>
                                        <option value="operators">Operators</option>
                                        <option value="contractors">Kontraktor</option>
                                        <option value="qhsse_team">Tim QHSSE</option>
                                    </select>
                                    <InputError message={errors.target_audience} className="mt-2" />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Tentukan siapa yang akan menerima campaign ini
                                    </p>
                                </div>

                                {/* Description */}
                                <div>
                                    <InputLabel htmlFor="description" value="Deskripsi Singkat" />
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Ringkasan singkat tentang campaign ini..."
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>

                                {/* Content */}
                                <div>
                                    <InputLabel htmlFor="content" value="Konten Campaign *" />
                                    <textarea
                                        id="content"
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        rows={8}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Tulis konten lengkap campaign di sini..."
                                        required
                                    />
                                    <InputError message={errors.content} className="mt-2" />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Konten ini akan ditampilkan kepada target audience
                                    </p>
                                </div>

                                {/* Expires At */}
                                <div>
                                    <InputLabel htmlFor="expires_at" value="Tanggal Kadaluarsa (Opsional)" />
                                    <TextInput
                                        id="expires_at"
                                        type="date"
                                        value={data.expires_at}
                                        onChange={(e) => setData('expires_at', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.expires_at} className="mt-2" />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Campaign akan otomatis expired setelah tanggal ini
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Campaign Tips */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 className="text-sm font-semibold text-blue-900 mb-2">💡 Tips Membuat Campaign Efektif</h3>
                            <ul className="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                <li>Gunakan judul yang jelas dan menarik perhatian</li>
                                <li>Tulis konten yang ringkas namun informatif</li>
                                <li>Pilih tipe dan target audience yang tepat</li>
                                <li>Safety Alert harus segera di-publish untuk awareness cepat</li>
                                <li>Policy Update sebaiknya disertai attachment dokumen</li>
                                <li>Gunakan tanggal kadaluarsa untuk campaign time-sensitive</li>
                            </ul>
                        </div>

                        {/* Status Info for Edit */}
                        {isEdit && (
                            <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h3 className="text-sm font-semibold text-gray-900 mb-2">ℹ️ Status Campaign</h3>
                                <div className="text-sm text-gray-700 space-y-1">
                                    <p><strong>Status:</strong> {campaign.status === 'draft' ? '📝 Draft' : campaign.status === 'published' ? '✅ Published' : '⏰ Expired'}</p>
                                    {campaign.published_at && (
                                        <p><strong>Published:</strong> {new Date(campaign.published_at).toLocaleString('id-ID')}</p>
                                    )}
                                    {campaign.status === 'draft' && (
                                        <p className="text-amber-700">⚠️ Campaign masih dalam status draft. Publish untuk menampilkan ke target audience.</p>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Action Buttons */}
                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <SecondaryButton
                                type="button"
                                onClick={() => router.visit(route('campaigns.index'))}
                            >
                                Batal
                            </SecondaryButton>
                            <PrimaryButton type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : (isEdit ? 'Update Campaign' : 'Simpan sebagai Draft')}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
