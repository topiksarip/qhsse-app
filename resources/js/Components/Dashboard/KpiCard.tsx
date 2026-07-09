type KpiCardProps = {
    label: string;
    value: number | string;
    tone?: 'emerald' | 'sky' | 'amber' | 'indigo';
};

const tones = {
    emerald: 'from-emerald-500 to-teal-600 shadow-emerald-900/10',
    sky: 'from-sky-500 to-cyan-600 shadow-sky-900/10',
    amber: 'from-amber-500 to-orange-600 shadow-amber-900/10',
    indigo: 'from-indigo-500 to-blue-700 shadow-indigo-900/10',
};

export default function KpiCard({ label, value, tone = 'indigo' }: KpiCardProps) {
    return (
        <div className={`overflow-hidden rounded-2xl bg-gradient-to-br ${tones[tone]} p-5 text-white shadow-xl`}>
            <div className="text-sm font-medium uppercase tracking-[0.2em] text-white/70">{label}</div>
            <div className="mt-4 text-4xl font-black tracking-tight">{value}</div>
            <div className="mt-4 h-1.5 rounded-full bg-white/20">
                <div className="h-1.5 w-2/3 rounded-full bg-white/70" />
            </div>
        </div>
    );
}
