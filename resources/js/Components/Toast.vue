<script setup>
import { useToast } from '@/Composables/useToast';
import { TransitionGroup } from 'vue';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div class="fixed top-4 right-4 z-50">
        <TransitionGroup name="toast">
            <div v-for="toast in toasts" :key="toast.id"
                 class="mb-4 p-4 rounded-lg shadow-lg min-w-[300px] max-w-[400px]"
                 :class="[
                     toast.severity === 'error' ? 'bg-red-50 text-red-700 border-l-4 border-red-500' :
                     toast.severity === 'success' ? 'bg-green-50 text-green-700 border-l-4 border-green-500' :
                     'bg-blue-50 text-blue-700 border-l-4 border-blue-500'
                 ]">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-medium">{{ toast.summary }}</h3>
                        <p class="text-sm mt-1">{{ toast.detail }}</p>
                    </div>
                    <button @click="removeToast(toast.id)" class="text-gray-500 hover:text-gray-700">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>
