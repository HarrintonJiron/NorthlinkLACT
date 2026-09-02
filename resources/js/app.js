import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

const pages = import.meta.glob('./Pages/**/*.vue')

window.addEventListener('vite:preloadError', (event) => {
    event.preventDefault()
    window.location.reload()
})

createInertiaApp({
    title: (title) => `${title} - Northlink LACT`,
    resolve: name => {
        const page = pages[`./Pages/${name}.vue`]

        if (!page) {
            throw new Error(`Página de Inertia no encontrada: ${name}`)
        }

        return page()
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
    progress: {
        color: '#007AFF',
        delay: 150,
    },
})
