import { EnvironmentalType } from '@/types';
import { SetDataAction } from '@inertiajs/react';
import { Errors } from '@inertiajs/core';

interface FormData {
    type: string;
    title: string;
    description: string;
    site_id: string | number;
    area_id: string | number;
    occurred_at: string;
    measured_value: string | number;
    unit: string;
    limit_value: string | number;
    waste_type: string;
    quantity: string | number;
    disposal_method: string;
    material: string;
    volume: string | number;
    containment: string;
    parameter: string;
    location: string;
}

interface TypeFieldsProps {
    type: EnvironmentalType | '';
    data: FormData;
    setData: SetDataAction<FormData>;
    errors: Errors;
    unitOptions: string[];
}

const inputClass =
    'w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const errClass = 'mt-1 text-sm text-red-600 dark:text-red-400';

function Field({ label, required, error, children }: { label: string; required?: boolean; error?: string; children: React.ReactNode }) {
    return (
        <div>
            <span className={labelClass}>
                {label} {required && <span className="text-red-500">*</span>}
            </span>
            {children}
            {error && <p className={errClass}>{error}</p>}
        </div>
    );
}

export default function TypeFields({ type, data, setData, errors, unitOptions }: TypeFieldsProps) {
    if (!type) return null;

    const unitSel = (
        <select value={data.unit} onChange={(e) => setData('unit', e.target.value)} className={inputClass}>
            <option value="">— Satuan —</option>
            {unitOptions.map((u) => (
                <option key={u} value={u}>{u}</option>
            ))}
        </select>
    );

    if (type === 'waste') {
        return (
            <>
                <Field label="Jenis Limbah" required error={errors.waste_type}>
                    <input type="text" value={data.waste_type} onChange={(e) => setData('waste_type', e.target.value)} className={inputClass} placeholder="Limbah B3 / Non-B3 / Medis..." />
                </Field>
                <div className="grid grid-cols-3 gap-2">
                    <Field label="Jumlah" required error={errors.quantity}>
                        <input type="number" step="0.0001" value={String(data.quantity)} onChange={(e) => setData('quantity', e.target.value)} className={inputClass} />
                    </Field>
                    <div className="col-span-2">
                        <span className={labelClass}>Satuan</span>
                        {unitSel}
                    </div>
                </div>
                <Field label="Metode Pembuangan" required error={errors.disposal_method}>
                    <input type="text" value={data.disposal_method} onChange={(e) => setData('disposal_method', e.target.value)} className={inputClass} placeholder="Incinerasi / TPA / Pihak Ketiga..." />
                </Field>
            </>
        );
    }

    if (type === 'spill') {
        return (
            <>
                <Field label="Material" required error={errors.material}>
                    <input type="text" value={data.material} onChange={(e) => setData('material', e.target.value)} className={inputClass} placeholder="Minyak / Kimia / Solar..." />
                </Field>
                <div className="grid grid-cols-3 gap-2">
                    <Field label="Volume" required error={errors.volume}>
                        <input type="number" step="0.0001" value={String(data.volume)} onChange={(e) => setData('volume', e.target.value)} className={inputClass} />
                    </Field>
                    <div className="col-span-2">
                        <span className={labelClass}>Satuan</span>
                        {unitSel}
                    </div>
                </div>
                <Field label="Penahanan" required error={errors.containment}>
                    <input type="text" value={data.containment} onChange={(e) => setData('containment', e.target.value)} className={inputClass} placeholder="Boom oil / Absorbent / Dike..." />
                </Field>
            </>
        );
    }

    if (type === 'emission' || type === 'water_monitoring') {
        return (
            <>
                <Field label="Parameter" required error={errors.parameter}>
                    <input type="text" value={data.parameter} onChange={(e) => setData('parameter', e.target.value)} className={inputClass} placeholder={type === 'emission' ? 'SOx, NOx, CO, PM10' : 'pH, TSS, BOD, COD'} />
                </Field>
                <div className="grid grid-cols-3 gap-2">
                    <Field label="Nilai Terukur" required error={errors.measured_value}>
                        <input type="number" step="0.0001" value={String(data.measured_value)} onChange={(e) => setData('measured_value', e.target.value)} className={inputClass} />
                    </Field>
                    <div className="col-span-2">
                        <span className={labelClass}>Satuan</span>
                        {unitSel}
                    </div>
                </div>
                <Field label="Batas" required error={errors.limit_value}>
                    <input type="number" step="0.0001" value={String(data.limit_value)} onChange={(e) => setData('limit_value', e.target.value)} className={inputClass} placeholder="Nilai batas regulasi" />
                </Field>
                <p className="text-xs text-gray-500 dark:text-gray-400">ⓘ Exceedance akan otomatis terdeteksi jika nilai terukur melebihi batas.</p>
            </>
        );
    }

    if (type === 'noise') {
        return (
            <>
                <div className="grid grid-cols-3 gap-2">
                    <Field label="Tingkat Kebisingan" required error={errors.measured_value}>
                        <input type="number" step="0.0001" value={String(data.measured_value)} onChange={(e) => setData('measured_value', e.target.value)} className={inputClass} />
                    </Field>
                    <div className="col-span-2">
                        <span className={labelClass}>Satuan (dB)</span>
                        <input disabled value="dB" className={`${inputClass} opacity-70`} />
                    </div>
                </div>
                <Field label="Lokasi Pengukuran" required error={errors.location}>
                    <input type="text" value={data.location} onChange={(e) => setData('location', e.target.value)} className={inputClass} placeholder="Stack area / Genset room..." />
                </Field>
                <Field label="Batas" required error={errors.limit_value}>
                    <input type="number" step="0.0001" value={String(data.limit_value)} onChange={(e) => setData('limit_value', e.target.value)} className={inputClass} placeholder="Nilai batas (dB)" />
                </Field>
                <p className="text-xs text-gray-500 dark:text-gray-400">ⓘ Exceedance akan otomatis terdeteksi jika nilai terukur melebihi batas.</p>
            </>
        );
    }

    // other
    return (
        <>
            <div className="grid grid-cols-3 gap-2">
                <Field label="Nilai Terukur">
                    <input type="number" step="0.0001" value={String(data.measured_value)} onChange={(e) => setData('measured_value', e.target.value)} className={inputClass} />
                </Field>
                <div className="col-span-2">
                    <span className={labelClass}>Satuan</span>
                    {unitSel}
                </div>
            </div>
            <Field label="Batas">
                <input type="number" step="0.0001" value={String(data.limit_value)} onChange={(e) => setData('limit_value', e.target.value)} className={inputClass} />
            </Field>
        </>
    );
}
