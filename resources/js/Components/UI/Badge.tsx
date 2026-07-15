import { PropsWithChildren } from 'react';

type Tone = 'gray' | 'emerald' | 'red' | 'amber' | 'blue' | 'indigo' | 'cyan' | 'violet';

const tones: Record<Tone, string> = {
    gray: 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-gray-200',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300',
    red: 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300',
    amber: 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300',
    indigo: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300',
    cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/60 dark:text-cyan-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-900/60 dark:text-violet-300',
};

export default function Badge({
    tone = 'gray',
    className = '',
    children,
}: PropsWithChildren<{ tone?: Tone; className?: string }>) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${tones[tone]} ${className}`}
        >
            {children}
        </span>
    );
}
