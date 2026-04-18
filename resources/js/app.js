import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import '../css/app.css'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        // Handles Laravel package namespaces (::) and subdirectories
        const normalized = name.replace(/::/g, '/')
        const page = pages[`./Pages/${normalized}.vue`]

        if (!page) {
            throw new Error(`Page not found: ./Pages/${normalized}.vue`)
        }

        return page
    },
    progress: {
        color: '#2563eb', // A nice vibrant blue
        showSpinner: true,
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
})
