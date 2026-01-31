import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { i18n } from './i18n';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'The Hunger';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n);
        const locale = (props.initialPage.props.locale as string) || 'tr';
        if (typeof i18n.global.locale === 'object' && 'value' in i18n.global.locale) {
            (i18n.global.locale as { value: string }).value = locale;
        } else {
            (i18n.global as { locale: string }).locale = locale;
        }
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
