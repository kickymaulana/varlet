import { createInertiaApp } from '@inertiajs/vue3'
import Varlet from '@varlet/ui'
import '@varlet/ui/es/style'
import Vue3Lottie from 'vue3-lottie'

createInertiaApp({
    withApp(app) {
        // Daftarkan Varlet UI secara global ke instance Vue
        app.use(Varlet)
        app.use(Vue3Lottie)
    },
})
