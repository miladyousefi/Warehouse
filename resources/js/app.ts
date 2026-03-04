import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';
import { i18n } from './i18n';

async function bootstrapEcho() {
    try {
        let EchoClass: any;
        let PusherClass: any;

        try {
            const echoModuleName = 'laravel-echo';
            const pusherModuleName = 'pusher-js';
            const [{ default: Echo }, { default: Pusher }] = await Promise.all([
                import(/* @vite-ignore */ echoModuleName),
                import(/* @vite-ignore */ pusherModuleName),
            ]);
            EchoClass = Echo;
            PusherClass = Pusher;
        } catch {
            await loadScript('https://js.pusher.com/8.4.0/pusher.min.js');
            await loadScript('https://unpkg.com/laravel-echo@1.16.1/dist/echo.iife.js');
            const w = window as any;
            EchoClass = w.Echo;
            PusherClass = w.Pusher;
        }

        if (!EchoClass || !PusherClass) return;

        const key = import.meta.env.VITE_PUSHER_APP_KEY as string | undefined;
        if (!key) return;

        const cluster = (import.meta.env.VITE_PUSHER_APP_CLUSTER as string | undefined) || 'mt1';
        const scheme = (import.meta.env.VITE_PUSHER_SCHEME as string | undefined) || 'https';
        const host = (import.meta.env.VITE_PUSHER_HOST as string | undefined)?.trim();
        const port = Number((import.meta.env.VITE_PUSHER_PORT as string | undefined) || (scheme === 'https' ? 443 : 80));

        const config: Record<string, unknown> = {
            broadcaster: 'pusher',
            key,
            cluster,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
        };

        const isHostedPusherApiHost = !!host && /^api-[a-z0-9-]+\.pusher\.com$/i.test(host);
        if (host && !isHostedPusherApiHost) {
            config.wsHost = host;
            config.wsPort = port;
            config.wssPort = port;
        }

        const w = window as any;
        w.Pusher = PusherClass;
        w.Echo = new EchoClass(config as any);
    } catch (error) {
        // Realtime is optional; app remains usable without Echo.
        console.warn('Echo bootstrap failed:', error);
    }
}

void bootstrapEcho();

function loadScript(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector<HTMLScriptElement>(`script[src="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => {
            script.dataset.loaded = 'true';
            resolve();
        };
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    });
}

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
