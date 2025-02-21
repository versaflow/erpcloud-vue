<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icons/Index.vue';

const props = defineProps({
    conversations: {
        type: Array,
        required: true,
        default: () => []
    },
    selectedConversation: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['select']);

const statusColors = {
    'new': 'bg-blue-50 text-blue-700 border-blue-100',
    'open': 'bg-green-50 text-green-700 border-green-100',
    'pending': 'bg-yellow-50 text-yellow-700 border-yellow-100',
    'resolved': 'bg-gray-50 text-gray-600 border-gray-100',
    'closed': 'bg-gray-50 text-gray-600 border-gray-100',
    'spam': 'bg-red-50 text-red-700 border-red-100'
};

const getTimeDisplay = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    if (days === 0) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (days === 1) {
        return 'Yesterday';
    } else if (days < 7) {
        return date.toLocaleDateString([], { weekday: 'short' });
    } else {
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }
};
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="p-4 border-b">
            <h2 class="text-lg font-medium">Conversations</h2>
        </div>

        <!-- Conversation List -->
        <div class="flex-1 overflow-y-auto">
            <div v-if="conversations.length === 0" 
                 class="flex flex-col items-center justify-center h-full text-gray-500 p-4">
                <Icon name="chat" size="8" class="text-gray-400 mb-2" />
                <p>No conversations found</p>
            </div>

            <div v-else class="divide-y">
                <div v-for="conversation in conversations" 
                     :key="conversation.id"
                     @click="emit('select', conversation)"
                     :class="[
                         'p-4 transition-colors cursor-pointer hover:bg-gray-50',
                         selectedConversation?.id === conversation.id ? 'bg-indigo-50' : '',
                         conversation.status === 'new' ? 'border-l-4 border-indigo-500' : ''
                     ]"
                >
                    <!-- Conversation Header -->
                    <div class="flex justify-between items-start mb-1">
                        <div class="flex items-center gap-3">
                            <!-- User Avatar -->
                            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-medium">
                                {{ conversation.user?.initials }}
                            </div>
                            
                            <!-- User Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ conversation.user?.name || conversation.from_email }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ conversation.department || 'No Department' }}
                                </p>
                            </div>
                        </div>

                        <!-- Timestamp -->
                        <span class="text-xs text-gray-500 whitespace-nowrap ml-2">
                            {{ getTimeDisplay(conversation.created_at) }}
                        </span>
                    </div>

                    <!-- Subject and Preview -->
                    <div class="ml-11">
                        <h3 class="text-sm font-medium text-gray-900 truncate">
                            {{ conversation.subject }}
                        </h3>
                        <p class="text-sm text-gray-500 truncate">
                            {{ conversation.messages?.[0]?.content?.replace(/<[^>]*>/g, '') || 'No message content' }}
                        </p>
                    </div>

                    <!-- Tags and Status -->
                    <div class="mt-2 ml-11 flex items-center gap-2">
                        <!-- Status Badge -->
                        <span :class="[
                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                            statusColors[conversation.status]
                        ]">
                            {{ conversation.status }}
                        </span>

                        <!-- Tags -->
                        <div class="flex gap-1">
                            <span v-for="tag in conversation.user?.tags?.slice(0, 2)" 
                                  :key="tag"
                                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                {{ tag }}
                            </span>
                            <span v-if="(conversation.user?.tags?.length || 0) > 2"
                                  class="text-xs text-gray-500">
                                +{{ conversation.user.tags.length - 2 }}
                            </span>
                        </div>

                        <!-- Attachment Indicator -->
                        <Icon v-if="conversation.messages?.some(m => m.has_attachments)"
                              name="paperclip"
                              size="4"
                              class="text-gray-400" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Optional: Add smooth scrolling */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #E5E7EB transparent;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: #E5E7EB;
    border-radius: 3px;
}
</style>
