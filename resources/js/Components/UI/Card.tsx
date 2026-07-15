import { HTMLAttributes, PropsWithChildren } from 'react';

type CardProps = HTMLAttributes<HTMLDivElement> & {
    padded?: boolean;
};

/**
 * Compact surface used across pages. Consistent border/radius/shadow and
 * light/dark styling. Pass `className` to override or extend.
 */
export default function Card({ padded = true, className = '', children, ...props }: PropsWithChildren<CardProps>) {
    return (
        <div
            {...props}
            className={
                `rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 ` +
                (padded ? 'p-4 sm:p-5 ' : '') +
                className
            }
        >
            {children}
        </div>
    );
}

export function CardHeader({ className = '', children, ...props }: PropsWithChildren<HTMLAttributes<HTMLDivElement>>) {
    return (
        <div {...props} className={`mb-3 flex items-center justify-between gap-3 ${className}`}>
            {children}
        </div>
    );
}

export function CardTitle({ className = '', children, ...props }: PropsWithChildren<HTMLAttributes<HTMLHeadingElement>>) {
    return (
        <h3 {...props} className={`text-sm font-semibold text-slate-800 dark:text-slate-100 ${className}`}>
            {children}
        </h3>
    );
}
