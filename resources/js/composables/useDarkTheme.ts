import { onMounted, onUnmounted } from 'vue'
import { StyleProvider, Themes } from '@varlet/ui'

export function useDarkTheme() {
    onMounted(() => {
        StyleProvider(Themes.md3Dark)
    })

    onUnmounted(() => {
        StyleProvider(Themes.md3Light)
    })
}