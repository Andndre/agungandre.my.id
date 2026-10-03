import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type ThemeState = {
    appearance: {
        value: Appearance;
    };
    resolvedAppearance: () => ResolvedAppearance;
    updateAppearance: (value: Appearance) => void;
};

const appearance = $state<{ value: Appearance }>({ value: 'system' });
const systemTheme = $state({ dark: false });

let themeChangeMediaQuery: MediaQueryList | null = null;

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return systemTheme.dark;
};

const isDarkMode = (value: Appearance): boolean => {
    return value === 'dark' || (value === 'system' && prefersDark());
};

const getResolvedAppearance = (): ResolvedAppearance => {
    return isDarkMode(appearance.value) ? 'dark' : 'light';
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (value: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark = isDarkMode(value);
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'system';
    }

    let stored: string | null =
        document.documentElement.dataset.appearance ?? null;

    try {
        stored = localStorage.getItem('appearance') ?? stored;
    } catch {
        // Session theme remains usable without persistent storage.
    }

    return stored === 'light' || stored === 'dark' || stored === 'system'
        ? stored
        : 'system';
};

const handleSystemThemeChange = (): void => {
    systemTheme.dark = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    applyTheme(appearance.value);
};

const detachThemeChangeListener = (): void => {
    if (!themeChangeMediaQuery) {
        return;
    }

    themeChangeMediaQuery.removeEventListener(
        'change',
        handleSystemThemeChange,
    );
    themeChangeMediaQuery = null;
};

export function initializeTheme(): () => void {
    if (typeof window === 'undefined') {
        return () => {};
    }

    systemTheme.dark = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    appearance.value = getStoredAppearance();
    applyTheme(appearance.value);

    detachThemeChangeListener();
    themeChangeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    themeChangeMediaQuery.addEventListener('change', handleSystemThemeChange);

    return detachThemeChangeListener;
}

export function updateAppearance(value: Appearance): void {
    appearance.value = value;

    if (typeof window !== 'undefined') {
        try {
            localStorage.setItem('appearance', value);
        } catch {
            // Cookie and in-memory preference still work.
        }
    }

    setCookie('appearance', value);
    applyTheme(value);
}

export function themeState(): ThemeState {
    return {
        appearance,
        resolvedAppearance: getResolvedAppearance,
        updateAppearance,
    };
}
