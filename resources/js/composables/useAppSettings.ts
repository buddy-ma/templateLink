import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import type { AppAuth, AppBranding, AppLocalization, AppSettings, AppTheme } from '@/types';

/** Safe fallbacks when `appSettings` is not yet on the page (e.g. edge of Inertia hydration). */
const defaultAppSettings: AppSettings = {
    branding: {
        appName: 'Laravel',
        logoUrl: null,
        faviconUrl: null,
        primaryColor: '0 0% 9%',
        primaryForegroundColor: '0 0% 98%',
        sidebarPrimaryColor: '0 0% 10%',
        fontSource: 'preset',
        fontPreset: 'instrument-sans',
        googleFontFamily: 'Poppins',
        fontUploadUrl: null,
        fontFaceName: null,
        fontStack: 'ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji"',
        googleFontStylesheetUrl: null,
    },
    localization: {
        defaultLocale: 'en',
        supportedLocales: ['en'],
        currentLocale: 'en',
        timezone: 'UTC',
    },
    theme: {
        defaultAppearance: 'system',
        forceAppearance: null,
    },
    auth: {
        zohoEnabled: false,
        passwordLoginEnabled: true,
    },
};

function resolveAppSettings(raw: unknown): AppSettings {
    if (raw && typeof raw === 'object' && raw !== null && 'branding' in raw) {
        return raw as AppSettings;
    }
    return defaultAppSettings;
}

export function useAppSettings(): ComputedRef<AppSettings> {
    const page = usePage();
    return computed(() => resolveAppSettings(page.props?.appSettings));
}

export function useBranding(): ComputedRef<AppBranding> {
    return computed(() => useAppSettings().value.branding);
}

export function useLocalization(): ComputedRef<AppLocalization> {
    return computed(() => useAppSettings().value.localization);
}

export function useThemeSettings(): ComputedRef<AppTheme> {
    return computed(() => useAppSettings().value.theme);
}

export function useAuthSettings(): ComputedRef<AppAuth> {
    return computed(() => useAppSettings().value.auth);
}

export function useAppName(): ComputedRef<string> {
    return computed(() => useAppSettings().value.branding.appName);
}
