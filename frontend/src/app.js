import { createApp, h } from 'vue'
import { createPinia } from 'pinia'
import { createInertiaApp } from '@inertiajs/vue3'
import DefaultLayout from './layouts/DefaultLayout.vue'
import './style.css'

createInertiaApp({
  title: (title) => (title ? `${title} — EduCore` : 'EduCore'),
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
    const page = pages[`./pages/${name}.vue`]

    if (!page) {
      throw new Error(`Inertia page "${name}" not found in ./pages/`)
    }

    page.default.layout = page.default.layout ?? DefaultLayout

    return page
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .mount(el)
  },
})