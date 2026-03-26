import type { LocaleMessages, VueMessageType } from 'vue-i18n';
import { createI18n } from 'vue-i18n';

type MessageSchema = Record<string, unknown>;

// Eagerly load all locale message files from lang/
const messages = Object.fromEntries(
    Object.entries(
        import.meta.glob<{ default: MessageSchema }>('../../lang/*.json', { eager: true }),
    ).map(([path, mod]) => {
        const locale = path.replace('../../lang/', '').replace('.json', '');
        return [locale, mod.default as LocaleMessages<VueMessageType>];
    }),
) as Record<string, LocaleMessages<VueMessageType>>;

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: (typeof document !== 'undefined' ? document.documentElement.lang : '') || 'en',
    fallbackLocale: 'en',
    messages,
});

export function setI18nLocale(locale: string): void {
    (i18n.global.locale as unknown as { value: string }).value = locale;
    if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('lang', locale);
        document.documentElement.setAttribute('dir', ['ar', 'he', 'fa', 'ur'].includes(locale) ? 'rtl' : 'ltr');
    }
}
