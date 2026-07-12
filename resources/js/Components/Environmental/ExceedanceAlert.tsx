import { EnvironmentalRecord } from '@/types';

export default function ExceedanceAlert({ record }: { record: EnvironmentalRecord }) {
    if (!record.is_exceedance) return null;
    const measured = Number(record.measured_value ?? 0);
    const limit = Number(record.limit_value ?? 0);
    const diff = (measured - limit).toFixed(2);
    const pct = limit > 0 ? (((measured - limit) / limit) * 100).toFixed(1) : '0.0';

    return (
        <div className="rounded-lg border-2 border-red-300 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
            <div className="flex items-start">
                <span className="mr-3 text-2xl">⚠</span>
                <div>
                    <h3 className="text-lg font-bold text-red-800 dark:text-red-200">EXCEEDANCE TERDETEKSI</h3>
                    <p className="text-sm text-red-700 dark:text-red-300">
                        Nilai terukur ({measured} {record.unit}) melebihi batas regulasi ({limit} {record.unit}).
                    </p>
                    <p className="mt-1 text-sm text-red-700 dark:text-red-300">
                        Selisih: {diff} {record.unit} ({pct}% di atas batas)
                    </p>
                    <p className="mt-2 text-xs text-red-600 dark:text-red-400">
                        Disarankan untuk membuka CAPA dan melakukan investigasi.
                    </p>
                </div>
            </div>
        </div>
    );
}
