<script setup>
import { useToast } from '@/Composables/useToast';
const { toasts } = useToast();
</script>

<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <div v-for="toast in toasts" 
             :key="toast.id"
             :class="[
                'px-4 py-2 rounded-lg shadow-lg transform transition-all duration-300',
                {
                    'bg-green-500 text-white': toast.type === 'success',
                    'bg-red-500 text-white': toast.type === 'error',
                }
             ]"
             class="animate-enter">
            {{ toast.message }}
        </div>
    </div>
</template>

<style scoped>
.animate-enter {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
