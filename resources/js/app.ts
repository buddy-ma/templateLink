import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { i18n, setI18nLocale } from '@/i18n';
import { initializeTheme } from '@/composables/useAppearance';
import type { AppSettings } from '@/types';

createInertiaApp({
    title: (title) => {
        const appName =
            (window as unknown as { __appSettings?: AppSettings }).__appSettings?.branding.appName
            ?? import.meta.env.VITE_APP_NAME
            ?? 'Laravel';
        return title ? `${title} - ${appName}` : appName;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const appSettings = (props.initialPage.props as { appSettings?: AppSettings }).appSettings;
        if (appSettings?.localization?.currentLocale) {
            setI18nLocale(appSettings.localization.currentLocale);
        }

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Initialize theme with server-provided default (falls back to 'system')
const pageData = document.getElementById('app')?.dataset?.page;
const initialAppearance = pageData
    ? (JSON.parse(pageData)?.props?.appSettings?.theme?.defaultAppearance ?? 'system')
    : 'system';
initializeTheme(initialAppearance);
