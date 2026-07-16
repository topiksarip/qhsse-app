import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import { FormEventHandler } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

interface Option { id: number; name: string }

type Props = {
    issuance: null;
    items: Array<Option & { item_number: string; track_type: string; serial_number?: string | null; catalog?: { name: string } }>;
    employees: Option[];
    contractors: Option[];
    locations: Option[];
    conditions: Record<string, string>;
};

export default function Form({ items, employees, contractors, locations, conditions }: PageProps<Props>) {
    const { data, setData, post, processing, errors } = useForm({
        apd_item_id: '',
        holder_type: 'employee',
        holder_id: '',
        quantity: 1,
        condition_out: '',
        issue_date: '',
        expected_return_date: '',
        expiry_date: '',
        notes: '',
        start_as_request: false,
    });

    const selectedItem = items.find((i) => String(i.id) === String(data.apd_item_id));
    const isSerial = selectedItem?.track_type === 'serial';

    const holderOptions: Option[] =
        data.holder_type === 'employee' ? employees : data.holder_type === 'contractor' ? contractors : locations;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/apd/issuances');
    };

    const inputCls =
        'mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100';

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <Link href="/apd/issuances" className="text-sm text-blue-600 hover:text-blue-900 mb-2 inline-block dark:text-blue-400">
                        ← Kembali ke Penugasan
                    </Link>
                    <h2 className="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Issue / Ajukan APD</h2>
                </div>
            }
        >
            <Head title="Issue APD" />
            <div className="py-6">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div>
                            <InputLabel htmlFor="apd_item_id" value="Item Stok" />
                            <select id="apd_item_id" className={inputCls} value={data.apd_item_id} onChange={(e) => setData('apd_item_id', e.target.value)}>
                                <option value="">— Pilih item —</option>
                                {items.map((i) => (
                                    <option key={i.id} value={i.id}>
                                        {i.item_number}
                                        {i.serial_number ? ` (${i.serial_number})` : ''} — {i.catalog?.name ?? '?'}
                                        {i.track_type === 'serial' ? ' [serial]' : ' [batch]'}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.apd_item_id} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="holder_type" value="Tipe Pemegang" />
                                <select
                                    id="holder_type"
                                    className={inputCls}
                                    value={data.holder_type}
                                    onChange={(e) => {
                                        setData('holder_type', e.target.value);
                                        setData('holder_id', '');
                                    }}
                                >
                                    <option value="employee">Karyawan</option>
                                    <option value="contractor">Kontraktor</option>
                                    <option value="location">Lokasi</option>
                                </select>
                                <InputError message={errors.holder_type} />
                            </div>
                            <div>
                                <InputLabel htmlFor="holder_id" value="Pemegang" />
                                <select id="holder_id" className={inputCls} value={data.holder_id} onChange={(e) => setData('holder_id', e.target.value)}>
                                    <option value="">— Pilih —</option>
                                    {holderOptions.map((o) => (
                                        <option key={o.id} value={o.id}>{o.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.holder_id} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel htmlFor="quantity" value="Jumlah" />
                                <TextInput id="quantity" type="number" min={1} className={inputCls} value={data.quantity} onChange={(e) => setData('quantity', Number(e.target.value))} disabled={isSerial} />
                                <InputError message={errors.quantity} />
                                {isSerial && <p className="mt-1 text-xs text-gray-400">Item serial = 1.</p>}
                            </div>
                            <div>
                                <InputLabel htmlFor="condition_out" value="Kondisi Saat Issue" />
                                <select id="condition_out" className={inputCls} value={data.condition_out} onChange={(e) => setData('condition_out', e.target.value)}>
                                    <option value="">— Pilih —</option>
                                    {Object.entries(conditions).map(([v, l]) => (
                                        <option key={v} value={v}>{l}</option>
                                    ))}
                                </select>
                                <InputError message={errors.condition_out} />
                            </div>
                            <div>
                                <InputLabel htmlFor="issue_date" value="Tgl Issue" />
                                <TextInput id="issue_date" type="date" className={inputCls} value={data.issue_date} onChange={(e) => setData('issue_date', e.target.value)} />
                                <InputError message={errors.issue_date} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="expected_return_date" value="Tgl Kembali (rencana)" />
                                <TextInput id="expected_return_date" type="date" className={inputCls} value={data.expected_return_date} onChange={(e) => setData('expected_return_date', e.target.value)} />
                                <InputError message={errors.expected_return_date} />
                            </div>
                            <div>
                                <InputLabel htmlFor="expiry_date" value="Kedaluwarsa Pemakaian" />
                                <TextInput id="expiry_date" type="date" className={inputCls} value={data.expiry_date} onChange={(e) => setData('expiry_date', e.target.value)} />
                                <InputError message={errors.expiry_date} />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="notes" value="Catatan" />
                            <textarea id="notes" className="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                            <InputError message={errors.notes} />
                        </div>

                        <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" checked={data.start_as_request} onChange={(e) => setData('start_as_request', e.target.checked)} />
                            Ajukan sebagai permintaan (butuh persetujuan)
                        </label>

                        <div className="flex gap-2">
                            <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                            <SecondaryButton href="/apd/issuances">Batal</SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
