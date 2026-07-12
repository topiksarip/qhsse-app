import { Link } from '@inertiajs/react';
import { EnvironmentalRecord } from '@/types';
import TypeBadge from './TypeBadge';
import ExceedanceBadge from './ExceedanceBadge';
import StatusBadge from './StatusBadge';

function fmt(dt?: string | null): string {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function RecordCard({ record }: { record: EnvironmentalRecord }) {
    const measured = record.measured_value != null ? `${record.measured_value} ${record.unit ?? ''}` : '-';
    const limit = record.limit_value != null ? `${record.limit_value} ${record.unit ?? ''}` : '-';

    return (
        <Link
            href={route('environment.records.show', record.id)}
            className={`block rounded-lg border bg-white p-4 shadow dark:bg-gray-800 ${
                record.is_exceedance ? 'border-l-4 border-red-500' : 'border-gray-200 dark:border-gray-700'
            }`}
        >
            <div className="flex items-center justify-between">
                <span className="font-mono text-sm text-gray-500 dark:text-gray-400">{record.record_number}</span>
                <div className="flex items-center gap-2">
                    <TypeBadge type={record.type} />
                    <ExceedanceBadge isExceedance={record.is_exceedance} />
                </div>
            </div>
            <p className="mt-1 font-medium text-gray-900 dark:text-gray-100">{record.title}</p>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {measured}
                {record.limit_value != null && ` > ${limit}`}
            </p>
            <div className="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{record.site?.name ?? '-'}</span>
                <span>{fmt(record.occurred_at)}</span>
            </div>
            <div className="mt-2 flex items-center justify-between">
                <StatusBadge status={record.status} />
                <span className="text-sm text-indigo-600 hover:underline dark:text-indigo-400">👁 Lihat</span>
            </div>
        </Link>
    );
}
