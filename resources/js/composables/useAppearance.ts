import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const appearance = ref<Appearance>('dark');
const systemPrefersDark = ref(false);
let systemThemeQuery: MediaQueryList | null = null;

const isAppearance = (value: string | null): value is Appearance =>
    value === 'light' || value === 'dark' || value === 'system';

const readCookieAppearance = (): Appearance | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const value = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('appearance='))
        ?.split('=')[1];

    return typeof value === 'string' && isAppearance(value) ? value : null;
};

const getStoredAppearance = (): Appearance | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const value = window.localStorage.getItem('appearance');

        return isAppearance(value) ? value : readCookieAppearance();
    } catch {
        return readCookieAppearance();
    }
};

const prefersDark = (): boolean =>
    systemPrefersDark.value;

const resolveAppearance = (value: Appearance): ResolvedAppearance => {
    if (value === 'system') {
        return prefersDark() ? 'dark' : 'light';
    }

    return value;
};

export function updateTheme(value: Appearance): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.appearance = value;
    document.documentElement.classList.toggle(
        'dark',
        resolveAppearance(value) === 'dark',
    );
}

const setCookie = (value: Appearance): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = 365 * 24 * 60 * 60;

    document.cookie = `appearance=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const handleSystemThemeChange = (event: MediaQueryListEvent): void => {
    systemPrefersDark.value = event.matches;

    if (appearance.value === 'system') {
        updateTheme('system');
    }
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const storedAppearance = getStoredAppearance() ?? 'dark';
    appearance.value = storedAppearance;
    systemPrefersDark.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    updateTheme(storedAppearance);

    if (!systemThemeQuery) {
        systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        systemThemeQuery.addEventListener('change', handleSystemThemeChange);
    }
}

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        initializeTheme();
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        resolveAppearance(appearance.value),
    );

    function updateAppearance(value: Appearance): void {
        appearance.value = value;

        try {
            window.localStorage.setItem('appearance', value);
        } catch {
            // The cookie still preserves the preference when local storage is unavailable.
        }

        setCookie(value);
        updateTheme(value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
