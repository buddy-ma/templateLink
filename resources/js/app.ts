import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h, Fragment } from 'vue';
import 'vue-sonner/style.css';
import '../css/app.css';
import GlobalToasts from '@/components/GlobalToasts.vue';
import { i18n, setI18nLocale } from '@/i18n';
import { initializeTheme } from '@/composables/useAppearance';
import type { AppSettings } from '@/types';

function resolveDocumentAppName(props: {
    name?: string;
    appSettings?: AppSettings;
}): string {
    return (
        props.appSettings?.branding?.appName ?? props.name ?? import.meta.env.VITE_APP_NAME ?? 'Laravel'
    );
}

let documentAppName = import.meta.env.VITE_APP_NAME ?? 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${documentAppName}` : documentAppName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const initial = props.initialPage.props as {
            name?: string;
            appSettings?: AppSettings;
        };
        documentAppName = resolveDocumentAppName(initial);

        const appSettings = initial.appSettings;
        if (appSettings?.localization?.currentLocale) {
            setI18nLocale(appSettings.localization.currentLocale);
        }

        const app = createApp({
            render: () => h(Fragment, [h(GlobalToasts), h(App, props)]),
        });
        app.use(plugin).use(i18n).mount(el);

        router.on('navigate', (event) => {
            documentAppName = resolveDocumentAppName(
                event.detail.page.props as { name?: string; appSettings?: AppSettings },
            );
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// Initialize theme with server-provided default / forced mode
const pageData = document.getElementById('app')?.dataset?.page;
const themeSettings = pageData
    ? JSON.parse(pageData)?.props?.appSettings?.theme
    : null;
initializeTheme(
    themeSettings?.defaultAppearance ?? 'system',
    themeSettings?.forceAppearance ?? null,
);
