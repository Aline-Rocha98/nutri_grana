import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const nomeApp = import.meta.env.VITE_APP_NAME || 'NutriGrana';

createInertiaApp({
    title: (titulo) => (titulo ? `${titulo} - ${nomeApp}` : nomeApp),
    resolve: (nome) =>
        resolvePageComponent(
            `./Pages/${nome}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#1fa67e',
    },
});
