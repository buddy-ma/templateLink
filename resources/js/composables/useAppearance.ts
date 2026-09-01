import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle(
            'dark',
            systemTheme === 'dark',
        );
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

const getStoredAppearance = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

const appearance = ref<Appearance>('system');
let forcedAppearance: Appearance | null = null;

export function initializeTheme(
    serverDefault: Appearance = 'system',
    forceAppearance: Appearance | null = null,
): void {
    if (typeof window === 'undefined') {
        return;
    }

    forcedAppearance =
        forceAppearance === 'light' || forceAppearance === 'dark'
            ? forceAppearance
            : null;

    // Forced theme wins; otherwise stored pref → server default → system
    const nextAppearance =
        forcedAppearance ?? getStoredAppearance() ?? serverDefault;

    appearance.value = nextAppearance;
    updateTheme(nextAppearance);

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance(options?: {
    defaultAppearance?: Appearance;
    forceAppearance?: Appearance | null;
}): UseAppearanceReturn {
    const forced = options?.forceAppearance ?? forcedAppearance;
    const serverDefault = options?.defaultAppearance ?? 'system';

    onMounted(() => {
        if (forced === 'light' || forced === 'dark') {
            forcedAppearance = forced;
            appearance.value = forced;
            updateTheme(forced);
            return;
        }

        const savedAppearance = getStoredAppearance();
        appearance.value = savedAppearance || serverDefault;
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return prefersDark() ? 'dark' : 'light';
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        if (forcedAppearance === 'light' || forcedAppearance === 'dark') {
            return;
        }

        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
