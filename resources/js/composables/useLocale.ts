import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { setI18nLocale } from '@/i18n';
import { useLocalization } from '@/composables/useAppSettings';

export function useLocale() {
    const { locale } = useI18n();
    const localization = useLocalization();

    function switchLocale(newLocale: string): void {
        setI18nLocale(newLocale);
        locale.value = newLocale;

        router.post(
            `/locale/${newLocale}`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Locale session is stored; page props will reflect on next visit
                },
            },
        );
    }

    return {
        currentLocale: locale,
        supportedLocales: localization.supportedLocales,
        switchLocale,
    };
}
