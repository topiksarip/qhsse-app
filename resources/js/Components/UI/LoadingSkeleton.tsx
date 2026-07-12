/**
 * LoadingSkeleton Component
 * 
 * Provides accessible loading state with animated skeleton placeholders.
 * Use instead of spinners for content areas to reduce layout shift.
 */

type LoadingSkeletonProps = {
    rows?: number;
    height?: 'sm' | 'md' | 'lg';
    className?: string;
};

const heightClasses = {
    sm: 'h-8',
    md: 'h-12',
    lg: 'h-16',
} as const;

export default function LoadingSkeleton({
    rows = 3,
    height = 'md',
    className = '',
}: LoadingSkeletonProps) {
    return (
        <div
            className={`space-y-3 ${className}`}
            role="status"
            aria-busy="true"
            aria-label="Loading content"
        >
            {Array.from({ length: rows }).map((_, i) => (
                <div
                    key={i}
                    className={`${heightClasses[height]} bg-slate-100 dark:bg-gray-800 animate-pulse rounded-lg`}
                    aria-hidden="true"
                />
            ))}
            <span className="sr-only">Loading...</span>
        </div>
    );
}
