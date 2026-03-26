import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AppAuth, AppBranding, AppLocalization, AppSettings, AppTheme } from '@/types';

export function useAppSettings(): AppSettings {
    const page = usePage();
    return page.props.appSettings as AppSettings;
}

export function useBranding(): AppBranding {
    return useAppSettings().branding;
}

export function useLocalization(): AppLocalization {
    return useAppSettings().localization;
}

export function useThemeSettings(): AppTheme {
    return useAppSettings().theme;
}

export function useAuthSettings(): AppAuth {
    return useAppSettings().auth;
}

export function useAppName() {
    return computed(() => useAppSettings().branding.appName);
}
