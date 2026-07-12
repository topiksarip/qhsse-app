export default function ExceedanceBadge({ isExceedance }: { isExceedance: boolean }) {
    if (!isExceedance) return null;
    return (
        <span className="inline-flex items-center rounded-full border border-red-300 bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:border-red-700 dark:bg-red-900 dark:text-red-200">
            🔴 Exceedance
        </span>
    );
}
