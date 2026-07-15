import { useCallback, useEffect, useState } from 'react';

type Theme = 'light' | 'dark';

function getInitialTheme(): Theme {
    if (typeof window === 'undefined') return 'light';
    const stored = localStorage.getItem('theme');
    if (stored === 'light' || stored === 'dark') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * Theme controller. Default follows OS preference; any manual toggle is
 * persisted to localStorage and wins across sessions. Applies the `dark`
 * class to <html> so Tailwind's class-based dark mode activates.
 */
export function useTheme() {
    const [theme, setThemeState] = useState<Theme>(getInitialTheme);

    useEffect(() => {
        const root = document.documentElement;
        if (theme === 'dark') root.classList.add('dark');
        else root.classList.remove('dark');
        try {
            localStorage.setItem('theme', theme);
        } catch (e) {
            /* ignore storage failures (private mode, etc.) */
        }
    }, [theme]);

    const toggle = useCallback(() => {
        setThemeState((prev) => (prev === 'dark' ? 'light' : 'dark'));
    }, []);

    return { theme, toggle, setTheme: setThemeState };
}
