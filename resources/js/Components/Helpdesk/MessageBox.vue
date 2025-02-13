<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icons/Index.vue';

const props = defineProps({
    message: {
        type: Object,
        required: true
    },
    isFirst: {
        type: Boolean,
        default: false
    }
});

const isEmail = computed(() => props.message.type === 'email');
const formattedDate = computed(() => {
    try {
        return new Date(props.message.created_at).toLocaleString();
    } catch (e) {
        return props.message.created_at;
    }
});

const isIncoming = computed(() => props.message.direction === 'incoming');

const getFileIcon = (type) => {
    if (type.startsWith('image/')) return 'image';
    if (type.startsWith('video/')) return 'video';
    if (type.includes('pdf')) return 'pdf';
    if (type.includes('word')) return 'document';
    if (type.includes('excel') || type.includes('sheet')) return 'spreadsheet';
    return 'file';
};

const getAttachmentSizeClass = (type) => ({
    'max-w-[200px] max-h-[200px] rounded-lg': type.startsWith('image/')
});
</script>

<template>
    <div class="bg-white border rounded-lg shadow-sm mb-4">
        <!-- Message Header -->
        <div class="p-3 border-b bg-gray-50">
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm">
                        {{ message.from_name?.[0] || 'U' }}
                    </div>
                    <div>
                        <div class="font-medium">{{ message.from_name || message.from_email }}</div>
                        <div class="text-xs text-gray-500">{{ message.from_email }}</div>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    {{ formattedDate }}
                </div>
            </div>
            <!-- Email Details -->
            <div v-if="isEmail" class="mt-2 pl-11 text-sm text-gray-600">
                <div class="grid grid-cols-[auto,1fr] gap-x-2">
                    <span class="font-medium">To:</span>
                    <span>{{ message.to_email }}</span>
                    
                    <template v-if="message.cc">
                        <span class="font-medium">CC:</span>
                        <span>{{ message.cc }}</span>
                    </template>

                    <template v-if="message.subject">
                        <span class="font-medium">Subject:</span>
                        <span>{{ message.subject }}</span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Message Content -->
        <div class="p-4">
            <div class="prose max-w-none" v-html="message.content"></div>

            <!-- Quote indicators for email threads -->
            <template v-if="message.quoted_text">
                <div class="mt-4 pl-4 border-l-4 border-gray-200 text-gray-600">
                    <div class="text-sm mb-2 text-gray-400">Original message:</div>
                    <div class="prose-sm" v-html="message.quoted_text"></div>
                </div>
            </template>

            <!-- Attachments with preview -->
            <div v-if="message.attachments?.length" class="mt-4 pt-4 border-t">
                <div class="text-sm font-medium text-gray-700 mb-2">
                    Attachments ({{ message.attachments.length }}):
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div v-for="attachment in message.attachments"
                         :key="attachment.id"
                         class="flex flex-col border rounded-lg overflow-hidden bg-gray-50">
                        <!-- Image preview -->
                        <img v-if="attachment.type?.startsWith('image/')"
                             :src="attachment.url"
                             :alt="attachment.name"
                             class="w-full h-32 object-cover" />
                        
                        <!-- File info -->
                        <div class="p-2">
                            <div class="flex items-center gap-2">
                                <Icon :name="getFileIcon(attachment.type)" size="4" class="text-gray-400" />
                                <span class="text-sm font-medium truncate">{{ attachment.name }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-500">{{ attachment.size }}</span>
                                <a :href="attachment.url" 
                                   target="_blank"
                                   class="text-xs text-indigo-600 hover:text-indigo-800">
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Footer -->
        <div v-if="message.signature || message.tags?.length" 
             class="px-4 py-3 border-t bg-gray-50">
            <!-- Email Signature -->
            <div v-if="message.signature" 
                 class="prose-sm text-gray-600 border-t border-gray-200 pt-2"
                 v-html="message.signature">
            </div>
            
            <!-- Tags -->
            <div v-if="message.tags?.length" class="flex gap-2 mt-2">
                <span v-for="tag in message.tags"
                      :key="tag"
                      class="px-2 py-0.5 text-xs bg-gray-200 text-gray-700 rounded-full">
                    {{ tag }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.prose) {
    max-width: none;
}

:deep(.prose img) {
    margin: 1rem 0;
    border-radius: 0.5rem;
}

:deep(.prose blockquote) {
    border-left-color: #e5e7eb;
    color: #6b7280;
}
</style>
