<script setup>
import { ref, computed } from 'vue';
import DOMPurify from 'dompurify';
import Icon from '@/Components/Icons/Index.vue';
import axios from 'axios';

const props = defineProps({
    message: {
        type: Object,
        required: true,

        default: () => ({
            content: '',
            created_at: '',
            attachments: [
            ],
            read_at: ""
            
        })
    },
    conversation: {
        type: Object,
        required: true
    }
});

const isUnread = computed(() => !props.message.read_at);

// Use conversation's user data instead of message's support user
const initials = computed(() => {
    const name = props.conversation.user.name;
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const formattedDate = computed(() => {
    try {
        return new Date(props.message.created_at).toLocaleString();
    } catch (e) {
        console.error('Date formatting error:', e);
        return props.message.created_at || 'Invalid date';
    }
});


const sanitizeHtml = (html) => {
    return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: [
            'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li',
            'span', 'div', 'blockquote', 'pre', 'code', 'hr', 'h1', 'h2',
            'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'td',
            'th', 'a', 'img', 'style', 'font', 'q', 'small', 'sub', 'sup'
        ],
        ALLOWED_ATTR: [
            'href', 'target', 'src', 'alt', 'class', 'style', 'id', 
            'width', 'height', 'align', 'valign', 'title', 'face',
            'size', 'color', 'background', 'bgcolor', 'border'
        ],
        ALLOWED_STYLES: [
            'color', 'background-color', 'font-size', 'font-family', 
            'text-align', 'margin', 'padding', 'border', 'width', 
            'height', 'display', 'white-space'
        ],
        WHOLE_DOCUMENT: false,
        SANITIZE_DOM: true
    });
};


const showThread = ref(false);
const hasQuotedText = computed(() => {
    return props.message.content.includes('On') && 
           props.message.content.includes('wrote:');
});

const splitContent = computed(() => {
    if (!props.message.content) return { main: '', quoted: '' };
    
    // Look for email thread markers
    const markers = [
        'On .* wrote:',
        '&gt; On .* wrote:',
        '-----Original Message-----',
        '&gt; -----Original Message-----'
    ];

    const regex = new RegExp(markers.join('|'));
    const parts = props.message.content.split(regex);

    return {
        main: parts[0],
        quoted: parts.length > 1 ? props.message.content.slice(parts[0].length) : ''
    };
});

const isAgentMessage = computed(() => {
    return props.message.user_id !== null;
});

const messageUser = computed(() => {
    if (isAgentMessage.value) {
        return props.message.agent || { name: 'Agent', email: '' };
    }
    return props.conversation.user;
});

const handleAttachmentClick = async (attachment) => {
    try {
        const response = await axios.get(`/helpdesk/attachments/${attachment.id}/download`, {
            responseType: 'blob'
        });

        // Create a blob URL
        const blob = new Blob([response.data], { 
            type: response.headers['content-type'] 
        });
        const url = window.URL.createObjectURL(blob);

        // For PDFs and images, open in new tab
        if (response.headers['content-type'].includes('pdf') || 
            response.headers['content-type'].includes('image')) {
            window.open(url, '_blank');
        } else {
            // For other files, trigger download
            const link = document.createElement('a');
            link.href = url;
            link.download = attachment.original_name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Clean up
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error downloading attachment:', error);
        alert('Failed to download attachment. Please try again.');
    }
};
</script>

<template>
    <div :class="[
        'mb-4 p-4 rounded-lg relative w-full', // Changed to w-full
        'bg-white',
        { 'border-l-4 border-indigo-500': isUnread && !isAgentMessage }
    ]">
        <!-- Message Header -->
        <div class="flex  justify-between items-start mb-2 border-b border-gray-200 pb-2"
          :class="{ 'flex-row-reverse': !isAgentMessage }"
        >
            <!-- Date (always on left) -->
            <div class="text-sm text-gray-500">
                {{ formattedDate }}
            </div>

            <!-- User/Agent info (right-aligned for agent messages) -->
            <div class="flex items-center gap-2" 
                 :class="{ 'flex-row-reverse': isAgentMessage }">
                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                     :class="isAgentMessage ? 'bg-indigo-100' : 'bg-gray-100'">
                    {{ initials }}
                </div>
                <div :class="{ 'text-right': isAgentMessage }">
                    <div class="font-medium">{{ messageUser.name }}</div>
                    <div class="text-sm text-gray-500">{{ messageUser.email }}</div>
                </div>
            </div>
        </div>

        <!-- Rest of the template remains unchanged -->
        <div class="mt-4">
            <div v-if="hasQuotedText" class="prose prose-sm max-w-none email-content">
                <!-- Main content -->
                <div v-html="sanitizeHtml(splitContent.main)" />
                
                <!-- Show/Hide Thread Button -->
                <button v-if="hasQuotedText"
                        @click="showThread = !showThread"
                        class="mt-2 text-sm text-gray-500 flex items-center gap-1 hover:text-gray-700">
                    <Icon :name="showThread ? 'chevron-up' : 'chevron-down'" size="4" />
                    {{ showThread ? 'Hide previous messages' : 'Show previous messages' }}
                </button>

                <!-- Quoted content -->
                <div v-show="showThread" 
                     class="mt-2 pl-4 border-l-2 border-gray-200 text-gray-600"
                     v-html="sanitizeHtml(splitContent.quoted)" />
            </div>
            <div v-else class="prose prose-sm max-w-none email-content"
                 v-html="sanitizeHtml(message.content)" />
        </div>

        <!-- Attachments -->
        <div v-if="message.attachments?.length" class="mt-4">
            <div class="text-sm font-medium text-gray-500 mb-2">Attachments:</div>
            <div class="flex flex-wrap gap-2">
                <button v-for="attachment in message.attachments" 
                        :key="attachment.id"
                        @click="handleAttachmentClick(attachment)"
                        class="inline-flex items-center gap-2 px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded">
                    <Icon name="paperclip" class="w-4 h-4" />
                    {{ attachment.name || attachment.original_name }}
                </button>
            </div>
        </div>
    </div>
</template>

<style>
/* Add Tailwind Typography styles for HTML email content */
.prose {
    max-width: none;
}

.prose pre {
    white-space: pre-wrap;
    background: #f3f4f6;
    padding: 1em;
    border-radius: 0.375rem;
}

.prose blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1em;
    color: #6b7280;
}

/* Enhanced email content styling */
.email-content {
    line-height: 1.5;
    overflow-wrap: break-word;
    word-wrap: break-word;
}

.email-content blockquote {
    margin: 0.5em 0;
    padding-left: 1em;
    border-left: 3px solid #e5e7eb;
    color: #4b5563;
}

.email-content pre {
    white-space: pre-wrap;
    background: #f3f4f6;
    padding: 1em;
    border-radius: 0.375rem;
    margin: 0.5em 0;
}

.email-content p {
    margin: 0.5em 0;
}

.email-content img {
    max-width: 100%;
    height: auto;
}

/* Handle nested email quotes */
.email-content blockquote blockquote {
    border-left-color: #d1d5db;
}

.email-content blockquote blockquote blockquote {
    border-left-color: #e5e7eb;
}

/* Table styles */
.email-content table {
    border-collapse: collapse;
    margin: 0.5em 0;
}

.email-content td,
.email-content th {
    border: 1px solid #e5e7eb;
    padding: 0.25em 0.5em;
}

/* Link styles */
.email-content a {
    color: #3b82f6;
    text-decoration: underline;
}

.email-content a:hover {
    color: #2563eb;
}

/* Add styles for email thread */
.email-content .thread-divider {
    border-top: 1px solid #e5e7eb;
    margin: 1rem 0;
}

.email-content .quoted-text {
    color: #6b7280;
    font-size: 0.95em;
}

/* Add transition for thread toggle */
.email-content [v-show] {
    transition: all 0.3s ease-in-out;
}
</style>
