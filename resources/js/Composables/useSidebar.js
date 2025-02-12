import { ref } from 'vue'

// Create a shared state that persists across components
const isCollapsed = ref(false)

export function useSidebar() {
    const toggleSidebar = () => {
        isCollapsed.value = !isCollapsed.value
    }

    return {
        isCollapsed,
        toggleSidebar
    }
}
