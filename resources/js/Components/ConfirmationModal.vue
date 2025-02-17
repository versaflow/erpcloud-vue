<script setup>
defineProps({
    show: Boolean,
    title: String,
    message: String,
    confirmLabel: {
        type: String,
        default: 'Confirm'
    },
    confirmStyle: {
        type: String,
        default: 'danger' // or 'warning', 'primary'
    }
});

const emit = defineEmits(['close', 'confirm']);

const confirmButtonClasses = {
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
    primary: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'
};
</script>

<template>
    <Transition name="modal">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="emit('close')">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-opacity-50 bg-blue-100 transition-opacity"></div>

            <!-- Modal -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                    <!-- Title -->
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        {{ title }}
                    </h3>

                    <!-- Content -->
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">{{ message }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex justify-end space-x-3">
                        <button
                            type="button"
                            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :class="[
                                'inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2',
                                confirmButtonClasses[confirmStyle]
                            ]"
                            @click="emit('confirm')"
                        >
                            {{ confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-to,
.modal-leave-from {
    opacity: 1;
}
</style>
