type KpiCardProps = {
    label: string;
    value: number | string;
    sub?: string;
    tone?: 'emerald' | 'sky' | 'amber' | 'indigo' | 'red';
};

const tones = {
    emerald: { bar: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400' },
    sky: { bar: 'bg-sky-500', text: 'text-sky-600 dark:text-sky-400' },
    amber: { bar: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' },
    indigo: { bar: 'bg-indigo-500', text: 'text-indigo-600 dark:text-indigo-400' },
    red: { bar: 'bg-red-500', text: 'text-red-600 dark:text-red-400' },
};

export default function KpiCard({ label, value, sub, tone = 'indigo' }: KpiCardProps) {
    const t = tones[tone];
    const overdue = sub?.toUpperCase().includes('OVERDUE');

    return (
        <div className="relative overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <span className={`absolute inset-y-0 left-0 w-1 ${t.bar}`} aria-hidden />
            <div className="pl-2">
                <div className="text-xs font-medium uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
                    {label}
                </div>
                <div className="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                    {value}
                </div>
                {sub && (
                    <div
                        className={`mt-1 text-xs font-medium ${
                            overdue ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400'
                        }`}
                    >
                        {sub}
                    </div>
                )}
            </div>
        </div>
    );
}
