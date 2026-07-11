type ChartPlaceholderProps = {
    title: string;
    description: string;
    points: number[];
    labels?: string[];
};

export default function ChartPlaceholder({ title, description, points, labels }: ChartPlaceholderProps) {
    const max = Math.max(...points, 1);

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-950 dark:text-white">{title}</h3>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>
                </div>
            </div>
            <div className="mt-6 flex h-40 items-end gap-3">
                {points.map((point, index) => (
                    <div key={`${point}-${index}`} className="flex flex-1 flex-col items-end justify-end">
                        <span className="mb-1 text-xs font-semibold text-slate-600 dark:text-slate-400">{point}</span>
                        <div className="w-full rounded-t-lg bg-gradient-to-t from-emerald-500 to-cyan-400 transition-all" style={{ height: `${Math.max((point / max) * 120, 8)}px` }} />
                        {labels && labels[index] && <span className="mt-1 text-xs text-slate-400">{labels[index]}</span>}
                    </div>
                ))}
            </div>
        </div>
    );
}
