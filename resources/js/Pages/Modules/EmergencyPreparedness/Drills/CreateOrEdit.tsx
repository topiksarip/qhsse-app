import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { EmergencyDrill, EmergencyPlan, Site, User, PageProps } from '@/types';

interface DrillFormProps extends PageProps {
    drill: EmergencyDrill | null;
    plans: EmergencyPlan[];
    sites: Site[];
    users: User[];
    can: {
        create: boolean;
        update: boolean;
    };
}

export default function CreateOrEdit({ auth, drill, plans, sites, users, can }: DrillFormProps) {
    const isEdit = drill !== null;
    const { data, setData, post, put, processing, errors } = useForm({
        emergency_plan_id: drill?.emergency_plan_id || '',
        site_id: drill?.site_id || '',
        scheduled_date: drill?.scheduled_date || '',
        observer_id: drill?.observer_id || '',
    });

    useEffect(() => {
        if (drill) {
            setData({
                emergency_plan_id: drill.emergency_plan_id || '',
                site_id: drill.site_id || '',
                scheduled_date: drill.scheduled_date || '',
                observer_id: drill.observer_id || '',
            });
        }
    }, [drill]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('emergency.drills.update', drill.id));
        } else {
            post(route('emergency.drills.store'));
        }
    };

    // Filter plans by selected site
    const filteredPlans = data.site_id
        ? plans.filter((plan) => String(plan.site_id) === String(data.site_id))
        : plans;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {isEdit ? 'Edit Latihan Darurat' : 'Jadwalkan Latihan Darurat'}
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Isi data latihan darurat dengan lengkap
                    </p>
                </div>
            }
        >
            <Head title={isEdit ? 'Edit Latihan Darurat' : 'Jadwalkan Latihan Darurat'} />

            <div className="py-6">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            {/* Informasi Latihan */}
                            <div className="mb-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                                    INFORMASI LATIHAN
                                </h3>

                                <div className="space-y-6">
                                    {/* Nomor Latihan (auto-generated, display only) */}
                                    {isEdit && (
                                        <div>
                                            <InputLabel value="Nomor Latihan" />
                                            <div className="mt-1 flex items-center gap-2">
                                                <TextInput
                                                    value={drill.drill_number}
                                                    disabled
                                                    className="block w-full bg-gray-100 dark:bg-gray-900"
                                                />
                                                <span className="text-gray-500 dark:text-gray-400 text-sm" title="Nomor latihan dibuat otomatis oleh sistem">
                                                    ⓘ
                                                </span>
                                            </div>
                                        </div>
                                    )}

                                    {/* Site */}
                                    <div>
                                        <InputLabel htmlFor="site_id" value="Site *" />
                                        <select
                                            id="site_id"
                                            name="site_id"
                                            value={data.site_id}
                                            onChange={(e) => {
                                                setData('site_id', e.target.value);
                                                // Reset plan when site changes
                                                setData('emergency_plan_id', '');
                                            }}
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

                                    {/* Rencana Darurat (filtered by site) */}
                                    <div>
                                        <InputLabel htmlFor="emergency_plan_id" value="Rencana Darurat *" />
                                        <select
                                            id="emergency_plan_id"
                                            name="emergency_plan_id"
                                            value={data.emergency_plan_id}
                                            onChange={(e) => setData('emergency_plan_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            required
                                            disabled={!data.site_id}
                                        >
                                            <option value="">— Pilih Rencana —</option>
                                            {filteredPlans.map((plan) => (
                                                <option key={plan.id} value={plan.id}>
                                                    {plan.plan_number} — {plan.name}
                                                </option>
                                            ))}
                                        </select>
                                        {!data.site_id && (
                                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                Pilih site terlebih dahulu
                                            </p>
                                        )}
                                        <InputError message={errors.emergency_plan_id} className="mt-2" />
                                    </div>

                                    {/* Tanggal Terjadwal */}
                                    <div>
                                        <InputLabel htmlFor="scheduled_date" value="Tanggal Terjadwal *" />
                                        <TextInput
                                            id="scheduled_date"
                                            type="date"
                                            name="scheduled_date"
                                            value={data.scheduled_date}
                                            className="mt-1 block w-full"
                                            onChange={(e) => setData('scheduled_date', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.scheduled_date} className="mt-2" />
                                    </div>

                                    {/* Observer */}
                                    <div>
                                        <InputLabel htmlFor="observer_id" value="Observer *" />
                                        <select
                                            id="observer_id"
                                            name="observer_id"
                                            value={data.observer_id}
                                            onChange={(e) => setData('observer_id', e.target.value)}
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
                                        <InputError message={errors.observer_id} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            {/* Action Bar */}
                            <div className="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('emergency.drills.index')}
                                    className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                >
                                    ← Batal
                                </Link>

                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
