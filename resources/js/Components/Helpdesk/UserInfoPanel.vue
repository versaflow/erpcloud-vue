<script setup>
import Icon from '@/Components/Icons/Index.vue';

defineProps({
    supportUser: {
        type: Object,
        required: true
    },
    userConversations: {
        type: Array,
        default: () => []
    }
});

const getStatusClass = (status) => ({
    'bg-green-100 text-green-800': status === 'open',
    'bg-gray-100 text-gray-800': ['resolved', 'closed'].includes(status),
    'bg-blue-100 text-blue-800': status === 'new',
    'bg-yellow-100 text-yellow-800': status === 'pending'
});
</script>

<template>
    <div class="w-80 bg-white rounded-lg shadow-sm flex flex-col">
        <!-- User Profile Header -->
        <div class="p-4 border-b bg-gray-50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center text-white text-lg font-medium">
                    {{ supportUser.initials }}
                </div>
                <div>
                    <h3 class="font-medium">{{ supportUser.name }}</h3>
                    <p class="text-sm text-gray-600">{{ supportUser.email }}</p>
                    <span class="text-xs text-gray-500">Customer since {{ supportUser.created_at }}</span>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="p-4 border-b">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Contact Information</h4>
            <div class="space-y-3 text-sm">
                <!-- Phone -->
                <div v-if="supportUser.phone" class="flex items-center gap-2">
                    <Icon name="phone" size="4" class="text-gray-400" />
                    {{ supportUser.phone }}
                </div>

                <!-- Company -->
                <div v-if="supportUser.company" class="flex items-center gap-2">
                    <Icon name="company" size="4" class="text-gray-400" />
                    {{ supportUser.company }}
                </div>

                <!-- Location -->
                <div v-if="supportUser.location" class="flex items-center gap-2">
                    <Icon name="location" size="4" class="text-gray-400" />
                    {{ supportUser.location }}
                </div>

                <!-- Timezone -->
                <div v-if="supportUser.timezone" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ supportUser.timezone }}
                </div>

                <!-- Last Contact -->
                <div class="flex items-center gap-2">
                    <Icon name="clock" size="4" class="text-gray-400" />
                    Last contact: {{ supportUser.last_contact || 'Never' }}
                </div>

                <!-- Total Conversations -->
                <div class="flex items-center gap-2">
                    <Icon name="chat" size="4" class="text-gray-400" />
                    Total conversations: {{ supportUser.total_conversations }}
                </div>
            </div>
        </div>

        <!-- Tags -->
        <div v-if="supportUser.tags?.length" class="p-4 border-b">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Tags</h4>
            <div class="flex flex-wrap gap-2">
                <span v-for="tag in supportUser.tags" 
                      :key="tag"
                      class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">
                    {{ tag }}
                </span>
            </div>
        </div>

        <!-- Notes -->
        <div v-if="supportUser.notes" class="p-4 border-b">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Notes</h4>
            <p class="text-sm text-gray-600">{{ supportUser.notes }}</p>
        </div>

        <!-- Previous Conversations -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Previous Conversations</h4>
                <div class="space-y-3">
                    <div v-for="conversation in userConversations" 
                         :key="conversation.id"
                         class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-start">
                            <h5 class="text-sm font-medium truncate">{{ conversation.subject }}</h5>
                            <span class="px-2 py-1 text-xs rounded-full"
                                  :class="getStatusClass(conversation.status)">
                                {{ conversation.status }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ conversation.created_at }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
