import { createInertiaApp } from '@inertiajs/vue3'
import Varlet, { Themes, StyleProvider } from '@varlet/ui'
import '@varlet/ui/es/style'

StyleProvider(Themes.md3Light)

createInertiaApp({
    withApp(app) {
        // Daftarkan Varlet UI secara global ke instance Vue
        app.use(Varlet)
    },
})
