import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { PropsWithChildren, ReactNode } from 'react';

/**
 * Consistent form row: label + control + error message with stable spacing.
 */
export default function Field({
    label,
    error,
    required,
    hint,
    children,
}: PropsWithChildren<{ label?: string; error?: string; required?: boolean; hint?: ReactNode }>) {
    return (
        <div className="space-y-1.5">
            {label && (
                <InputLabel value={label}>
                    {required && <span className="ms-0.5 text-red-500">*</span>}
                </InputLabel>
            )}
            {children}
            {hint && <p className="text-xs text-slate-500 dark:text-slate-400">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
