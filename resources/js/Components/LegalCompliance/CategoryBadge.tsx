import { LegalRegisterCategory } from '@/types';

const config: Record<LegalRegisterCategory, { label: string; color: string }> = {
    national: { label: 'Nasional', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    regional: { label: 'Regional', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' },
    industry: { label: 'Industri', color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' },
    internal: { label: 'Internal', color: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200' },
};

export default function CategoryBadge({ category }: { category: LegalRegisterCategory }) {
    const c = config[category] ?? config.internal;
    return <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${c.color}`}>{c.label}</span>;
}
