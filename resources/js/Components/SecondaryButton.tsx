import { ButtonHTMLAttributes, ReactNode } from 'react';
import { Link } from '@inertiajs/react';

type Size = 'sm' | 'md';

const base =
    'inline-flex items-center justify-center gap-2 rounded-md font-medium transition ' +
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ' +
    'disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-gray-900';

const sizes: Record<Size, string> = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
};

const variant =
    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 active:bg-slate-100 focus-visible:ring-emerald-500 ' +
    'dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:active:bg-gray-600';

type CommonProps = {
    size?: Size;
    loading?: boolean;
    href?: string;
    children?: ReactNode;
};

function Spinner() {
    return (
        <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
    );
}

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    size = 'md',
    loading = false,
    href,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & CommonProps) {
    const cls = `${base} ${sizes[size]} ${variant} ${className}`;

    if (href) {
        return (
            <Link href={href} className={cls}>
                {children}
            </Link>
        );
    }

    return (
        <button {...props} type={type} disabled={disabled || loading} className={cls}>
            {loading && <Spinner />}
            {children}
        </button>
    );
}
