export default function StatusBadge({ active }: { active: boolean }) {
    return (
        <span
            className={[
                'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                active
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200'
                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            ].join(' ')}
        >
            {active ? 'Active' : 'Inactive'}
        </span>
    );
}
