// import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Antd from 'ant-design-vue';
import VueApexCharts from 'vue3-apexcharts';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Apply saved appearance before any page setup (fixes charts / first paint on dark mode).
if (typeof window !== 'undefined') {
    initializeTheme();
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Antd)
            .use(VueApexCharts)
            .mount(el);

        delete el.dataset.page;

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
