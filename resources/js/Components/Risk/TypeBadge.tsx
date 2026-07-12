import { RiskRegisterType } from '@/types';

const config: Record<RiskRegisterType, { label: string; color: string }> = {
    hazard_identification: { label: 'Hazard ID', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
    jsa: { label: 'JSA', color: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200' },
    hiradc: { label: 'HIRADC', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    risk_assessment: { label: 'Risk Assessment', color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
};

export default function TypeBadge({ type }: { type: RiskRegisterType }) {
    const c = config[type] ?? config.jsa;
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${c.color}`}>
            {c.label}
        </span>
    );
}
