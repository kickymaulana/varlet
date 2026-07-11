import { createInertiaApp } from '@inertiajs/vue3'
import Varlet, { Themes, StyleProvider } from '@varlet/ui'
import '@varlet/ui/es/style'
import Vue3Lottie from 'vue3-lottie'

StyleProvider(Themes.md3Light)

createInertiaApp({
    withApp(app) {
        // Daftarkan Varlet UI secara global ke instance Vue
        app.use(Varlet)
        app.use(Vue3Lottie)
    },
})
