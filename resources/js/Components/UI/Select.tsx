import { SelectHTMLAttributes } from 'react';

const base =
    'block w-full rounded-md border-slate-300 shadow-sm transition ' +
    'focus:border-emerald-500 focus:ring-emerald-500 focus:ring-1 sm:text-sm ' +
    'disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 ' +
    'dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 ' +
    'dark:focus:border-emerald-400 dark:focus:ring-emerald-400 dark:disabled:bg-gray-900';

export default function Select({ className = '', children, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
    return (
        <select {...props} className={`${base} ${className}`}>
            {children}
        </select>
    );
}
