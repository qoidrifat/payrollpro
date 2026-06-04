import '../css/app.css';
import './bootstrap';

// Dark mode initialization
if (localStorage.getItem('darkMode') === 'true' ||
    (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import LoadingScreen from '@/Components/LoadingScreen.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Mount LoadingScreen as a separate global overlay
        const loadingEl = document.createElement('div');
        loadingEl.id = 'loading-screen';
        document.body.appendChild(loadingEl);
        createApp(LoadingScreen).mount(loadingEl);

        return app.mount(el);
    },
    progress: false, // disable default progress bar, using custom LoadingScreen
});
