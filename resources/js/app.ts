import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';
import vPreventDoubleClick from '@/directives/preventDoubleClick';
import { getEcho } from '@/echo';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * The XSRF-TOKEN cookie is HttpOnly, so Axios can no longer read it directly.
 * Laravel shares the same token as an Inertia prop; mirror it into the
 * X-CSRF-TOKEN header (and the meta tag used by offline sync) instead.
 */
function applyCsrfToken(props: Record<string, unknown>): void {
    const token = props.csrf_token;

    if (typeof token !== 'string' || token === '') {
        return;
    }

    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

    const meta = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    );

    if (meta) {
        meta.content = token;
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        applyCsrfToken(props.initialPage.props as Record<string, unknown>);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .directive('prevent-double-click', vPreventDoubleClick)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Keep the CSRF header fresh when the session token is rotated (e.g. logout).
router.on('navigate', (event) => {
    applyCsrfToken(event.detail.page.props as Record<string, unknown>);
});

// ── Global click guard — prevents rapid double-clicks on form submit buttons ──
const CLICK_COOLDOWN = new WeakMap<HTMLElement, number>();
const SUBMIT_COOLDOWN_MS = 1500;

document.addEventListener(
    'click',
    (e: MouseEvent) => {
        const target = (e.target as HTMLElement).closest<HTMLButtonElement>(
            'button[type="submit"], button:not([type]), input[type="submit"], input[type="image"]',
        );

        if (!target) {
            return;
        }

        const lastClick = CLICK_COOLDOWN.get(target) ?? 0;
        const now = Date.now();

        if (now - lastClick < SUBMIT_COOLDOWN_MS) {
            e.preventDefault();
            e.stopPropagation();

            return;
        }

        CLICK_COOLDOWN.set(target, now);
    },
    true,
);

// This will set light / dark mode on page load...
initializeTheme();

// Initialise WebSocket (only connects on client — SSR guard is inside getEcho)
getEcho();
