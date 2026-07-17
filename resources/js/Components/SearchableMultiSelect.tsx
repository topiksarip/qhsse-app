import { useState } from 'react';

type Option = { value: string; label: string };

function CheckIcon({ className = '' }: { className?: string }) {
    return (
        <svg className={`h-4 w-4 text-indigo-600 ${className}`} viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.1 3.1 6.8-6.8a1 1 0 011.4 0z" clipRule="evenodd" />
        </svg>
    );
}

function ChevronDownIcon({ className = '' }: { className?: string }) {
    return (
        <svg className={`ml-auto h-4 w-4 text-gray-400 ${className}`} viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clipRule="evenodd" />
        </svg>
    );
}

export default function SearchableMultiSelect({
    options,
    value,
    onChange,
    placeholder = 'Cari & pilih...',
}: {
    options: Option[];
    value: string[];
    onChange: (next: string[]) => void;
    placeholder?: string;
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');

    const filtered = options.filter((o) =>
        o.label.toLowerCase().includes(query.toLowerCase()),
    );

    const toggle = (v: string) => {
        if (value.includes(v)) {
            onChange(value.filter((x) => x !== v));
        } else {
            onChange([...value, v]);
        }
    };

    const selectedLabels = options
        .filter((o) => value.includes(o.value))
        .map((o) => o.label);

    return (
        <div className="relative">
            <div
                className="flex min-h-[42px] w-full flex-wrap items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-700"
                onClick={() => setOpen((o) => !o)}
            >
                {selectedLabels.length === 0 && (
                    <span className="text-sm text-gray-400">{placeholder}</span>
                )}
                {selectedLabels.map((label) => (
                    <span
                        key={label}
                        className="inline-flex items-center rounded bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                    >
                        {label}
                    </span>
                ))}
                <ChevronDownIcon className="ml-auto h-4 w-4 text-gray-400" />
            </div>

            {open && (
                <div className="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800">
                    <input
                        type="text"
                        autoFocus
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Cari..."
                        className="w-full border-b border-gray-200 px-3 py-2 text-sm focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    />
                    <div className="max-h-56 overflow-auto">
                        {filtered.length === 0 && (
                            <div className="px-3 py-2 text-sm text-gray-400">Tidak ada hasil</div>
                        )}
                        {filtered.map((o) => {
                            const checked = value.includes(o.value);
                            return (
                                <button
                                    type="button"
                                    key={o.value}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        toggle(o.value);
                                    }}
                                    className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <span className="text-gray-800 dark:text-gray-100">{o.label}</span>
                                    {checked && <CheckIcon className="h-4 w-4 text-indigo-600" />}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
