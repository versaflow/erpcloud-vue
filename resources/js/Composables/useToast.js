import { ref } from 'vue';

const toasts = ref([]);
let id = 0;

export function useToast() {
    const add = ({ severity = 'info', summary = '', detail = '', life = 3000 }) => {
        const toast = {
            id: id++,
            severity,
            summary,
            detail
        };
        
        toasts.value.push(toast);
        
        if (life) {
            setTimeout(() => {
                removeToast(toast.id);
            }, life);
        }
    };

    const removeToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    return {
        toasts,
        add,
        removeToast
    };
};
