import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

export function useToast() {
    const showToast = (options) => {
        if (typeof options === 'string') {
            options = { detail: options };
        }

        const toast = {
            id: nextId++,
            severity: options.severity || 'info',
            summary: options.summary || 'Message',
            detail: options.detail,
            life: options.life || 3000
        };

        toasts.value.push(toast);

        setTimeout(() => {
            removeToast(toast.id);
        }, toast.life);
    };

    const removeToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    return {
        toasts,
        showToast,
        removeToast
    };
}
