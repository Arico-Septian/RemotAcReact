export type Theme = 'dark' | 'light';

const STORAGE_KEY = 'smartac-theme';

export function getStoredTheme(): Theme {
    if (typeof window === 'undefined') return 'dark';
    return window.localStorage.getItem(STORAGE_KEY) === 'light' ? 'light' : 'dark';
}

export function applyTheme(theme: Theme): void {
    if (typeof document === 'undefined') return;
    document.documentElement.setAttribute('data-theme', theme);
    window.localStorage.setItem(STORAGE_KEY, theme);
}
