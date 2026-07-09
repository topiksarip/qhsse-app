type ChartPlaceholderProps = {
    title: string;
    description: string;
    points: number[];
};

export default function ChartPlaceholder({ title, description, points }: ChartPlaceholderProps) {
    const max = Math.max(...points, 1);

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-950 dark:text-white">{title}</h3>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>
                </div>
                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-slate-500 dark:bg-gray-800 dark:text-slate-300">Shell</span>
            </div>
            <div className="mt-8 flex h-40 items-end gap-3 rounded-xl bg-slate-50 p-4 dark:bg-gray-950">
                {points.map((point, index) => (
                    <div key={`${point}-${index}`} className="flex flex-1 items-end">
                        <div className="w-full rounded-t-lg bg-gradient-to-t from-emerald-500 to-cyan-400" style={{ height: `${Math.max((point / max) * 100, 10)}%` }} />
                    </div>
                ))}
            </div>
        </div>
    );
}
