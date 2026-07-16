type Series = { name: string; color: string; points: number[] };

type ChartPlaceholderProps = {
    title: string;
    description: string;
    points?: number[];
    labels?: string[];
    series?: Series[];
};

const FALLBACK_COLORS = ['from-emerald-500 to-cyan-400', 'from-red-500 to-rose-400', 'from-amber-500 to-yellow-400'];

export default function ChartPlaceholder({ title, description, points, labels, series }: ChartPlaceholderProps) {
    const useSeries = series && series.length > 0;
    const groupCount = useSeries ? series[0].points.length : points?.length ?? 0;
    const max = Math.max(...(useSeries ? series.flatMap((s) => s.points) : (points ?? [])), 1);

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-bold text-slate-950 dark:text-white">{title}</h3>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>
                </div>
            </div>

            {useSeries ? (
                <>
                    <div className="mt-4 flex flex-wrap gap-4">
                        {series.map((s) => (
                            <div key={s.name} className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <span className={`inline-block h-2.5 w-2.5 rounded-full bg-gradient-to-t ${s.color}`} />
                                {s.name}
                            </div>
                        ))}
                    </div>
                    <div className="mt-4 flex h-40 items-end gap-3">
                        {Array.from({ length: groupCount }).map((_, i) => (
                            <div key={i} className="flex flex-1 flex-col items-end justify-end">
                                <div className="flex w-full items-end justify-center gap-1">
                                    {series.map((s) => {
                                        const value = s.points[i] ?? 0;
                                        return (
                                            <div
                                                key={s.name}
                                                className={`w-full max-w-[10px] rounded-t-lg bg-gradient-to-t ${s.color} transition-all`}
                                                style={{ height: `${Math.max((value / max) * 120, 4)}px` }}
                                                title={`${s.name}: ${value}`}
                                            />
                                        );
                                    })}
                                </div>
                                {labels && labels[i] && <span className="mt-1 text-xs text-slate-400">{labels[i]}</span>}
                            </div>
                        ))}
                    </div>
                </>
            ) : (
                <div className="mt-6 flex h-40 items-end gap-3">
                    {(points ?? []).map((point, index) => (
                        <div key={`${point}-${index}`} className="flex flex-1 flex-col items-end justify-end">
                            <span className="mb-1 text-xs font-semibold text-slate-600 dark:text-slate-400">{point}</span>
                            <div className="w-full rounded-t-lg bg-gradient-to-t from-emerald-500 to-cyan-400 transition-all" style={{ height: `${Math.max((point / max) * 120, 8)}px` }} />
                            {labels && labels[index] && <span className="mt-1 text-xs text-slate-400">{labels[index]}</span>}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
