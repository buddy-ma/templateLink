import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { setI18nLocale } from '@/i18n';
import { useLocalization } from '@/composables/useAppSettings';

export function useLocale() {
    const { locale } = useI18n();
    const localization = useLocalization();

    const supportedLocales = computed(() => localization.value.supportedLocales);

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
                    router.reload({ only: ['appSettings', 'liveLocaleMessages', 'flash'] });
                },
            },
        );
    }

    return {
        currentLocale: locale,
        supportedLocales,
        switchLocale,
    };
}
