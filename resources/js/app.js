import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import '../css/toastify-custom.css';
import { processarFlash, registrarFlashNotificacao } from '@/Helpers/notificacao';

const nomeApp = import.meta.env.VITE_APP_NAME || 'NutriGrana';

createInertiaApp({
    title: (titulo) => titulo || nomeApp,
    resolve: (nome) =>
        resolvePageComponent(
            `./Pages/${nome}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Vue3Toastify, {
                autoClose: 4000,
                position: 'top-right',
                theme: 'colored',
                hideProgressBar: true,
                clearOnUrlChange: false,
            })
            .mount(el);

        processarFlash(props.initialPage?.props?.flash ?? {});
        registrarFlashNotificacao();
    },
    progress: {
        color: '#1fa67e',
    },
});
