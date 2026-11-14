import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

export type ThemePreference = 'light' | 'dark' | 'system';
export type ResolvedTheme = 'light' | 'dark';

const STORAGE_KEY = 'fnl.theme';

interface ThemeContextValue {
    /** What the user chose. */
    preference: ThemePreference;
    /** What is actually painted right now. */
    theme: ResolvedTheme;
    setPreference: (preference: ThemePreference) => void;
    /** light -> dark -> system -> light */
    cycle: () => void;
}

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function useTheme(): ThemeContextValue {
    const context = useContext(ThemeContext);
    if (!context) {
        throw new Error('useTheme() must be used inside a <ThemeProvider>.');
    }
    return context;
}

function readPreference(): ThemePreference {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark' || stored === 'system') {
            return stored;
        }
    } catch {
        // Private mode / blocked storage — fall through to the system default.
    }
    return 'system';
}

function systemTheme(): ResolvedTheme {
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * Writes the resolved theme onto `<html data-theme>` so CSS tokens and the
 * `dark:` variant agree, and keeps following the OS while the preference is
 * "system".
 */
export function ThemeProvider({ children }: { children: ReactNode }) {
    const [preference, setPreferenceState] = useState<ThemePreference>(() =>
        typeof window === 'undefined' ? 'system' : readPreference(),
    );
    const [system, setSystem] = useState<ResolvedTheme>(() =>
        typeof window === 'undefined' ? 'light' : systemTheme(),
    );

    useEffect(() => {
        const query = window.matchMedia('(prefers-color-scheme: dark)');
        const listener = (event: MediaQueryListEvent) => setSystem(event.matches ? 'dark' : 'light');
        query.addEventListener('change', listener);
        return () => query.removeEventListener('change', listener);
    }, []);

    const theme: ResolvedTheme = preference === 'system' ? system : preference;

    useEffect(() => {
        document.documentElement.dataset['theme'] = theme;
    }, [theme]);

    const setPreference = useCallback((next: ThemePreference) => {
        setPreferenceState(next);
        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Non-fatal: the choice just will not survive a reload.
        }
    }, []);

    const cycle = useCallback(() => {
        setPreferenceState((current) => {
            const next: ThemePreference =
                current === 'light' ? 'dark' : current === 'dark' ? 'system' : 'light';
            try {
                window.localStorage.setItem(STORAGE_KEY, next);
            } catch {
                // ignore
            }
            return next;
        });
    }, []);

    const value = useMemo<ThemeContextValue>(
        () => ({ preference, theme, setPreference, cycle }),
        [preference, theme, setPreference, cycle],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

/**
 * Inline script source for the Blade layout: applies the stored theme before
 * first paint so the dashboard never flashes light before turning dark.
 */
export const themeBootstrapScript = `(function(){try{var t=localStorage.getItem('${STORAGE_KEY}');if(t==='light'||t==='dark'){document.documentElement.dataset.theme=t;return}}catch(e){}document.documentElement.dataset.theme=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'})();`;
