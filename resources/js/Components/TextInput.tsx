import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
} from 'react';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'block w-full rounded-md border-slate-300 shadow-sm transition ' +
                'focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 sm:text-sm ' +
                'disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 ' +
                'dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500 ' +
                'dark:focus:border-emerald-400 dark:focus:ring-emerald-400 dark:disabled:bg-gray-900 ' +
                className
            }
            ref={localRef}
        />
    );
});
