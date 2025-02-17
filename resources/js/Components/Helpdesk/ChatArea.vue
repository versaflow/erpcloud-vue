<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icons/Index.vue';
import MessageBox from './MessageBox.vue';
import EmailEditor from './EmailEditor.vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast'; // Create this composable if you haven't already
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const { showToast } = useToast();

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
        default: () => ({
            id: null,
            status: '',
            subject: '',
            user: {},
            messages: [],
            created_at: null,
            department_id: null,
            agent_id: null
        })
    },
    departments: {
        type: Array,
        required: true
    },
    agents: {
        type: Array,
        required: true
    },
    signatures: { // Add signatures prop
        type: Array,
        required: true,
        default: () => []
    }
});

onMounted(() => {
   console.log('Conversation:', props.conversation);
});

const emit = defineEmits(['assign', 'send-message', 'mark-spam', 'archive', 'escalate', 'transfer']);

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.is_admin);
const currentUserId = computed(() => page.props.auth.user.id);

const showAssignMenu = ref(false);
const showTransferMenu = ref(false);
const selectedAgent = ref(null);

// Computed property to filter out current user from agents list for transfer
const availableAgents = computed(() => {
    return props.agents.filter(agent => agent.id !== currentUserId.value);
});

const message = ref('');
const assignedDepartment = ref(null);
const assignedAgent = ref(null);
const showEmailReply = ref(false);

// Initialize values when conversation changes
watch(() => props.conversation, (newConversation) => {
    if (newConversation) {
        assignedDepartment.value = newConversation.department_id || null;
        assignedAgent.value = newConversation.agent_id || null;
    }
}, { immediate: true });

// Add debugging watch for conversation prop
watch(() => props.conversation, (newConv) => {
    console.log('Conversation Data:', {
        id: newConv.id,
        created_at: newConv.created_at,
        raw_date: new Date(newConv.created_at),
        parsed: formatDate(newConv.created_at)
    });
}, { immediate: true });

// Add ref for messages container
const messagesContainer = ref(null);

// Add function to scroll to first unread message or end
const scrollToFirstUnreadOrEnd = () => {
    if (!messagesContainer.value) return;

    // Find first unread message
    const firstUnreadMessage = props.conversation.messages.find(m => !m.read_at);
    
    if (firstUnreadMessage) {
        // Find the message element
        const unreadElement = document.getElementById(`message-${firstUnreadMessage.id}`);
        if (unreadElement) {
            unreadElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    } else {
        // If no unread messages, scroll to bottom
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Add watch effect to mark messages as read when conversation changes
watch(() => props.conversation, async (newConversation) => {
    if (newConversation?.id) {
        nextTick(() => {
            scrollToFirstUnreadOrEnd();
        });
        try {
            await axios.post(`/helpdesk/conversations/${newConversation.id}/read`);
        } catch (error) {
            console.error('Failed to mark messages as read:', error);
        }
    }
}, { immediate: true });

const isSending = ref(false); // Add this ref

async function sendMessage() {
    if (!message.value.trim() || isSending.value) return;
    
    isSending.value = true;
    try {
        const response = await axios.post(
            `/helpdesk/conversations/${props.conversation.id}/messages`,
            {
                content: message.value,
                type: props.conversation.source,
                conversation_id: props.conversation.id
            }
        );

        message.value = '';
        emit('send-message', response.data.data);
        showToast('Message sent successfully', 'success');
    } catch (error) {
        console.error('Failed to send message:', error);
        showToast('Failed to send message', 'error');
    } finally {
        isSending.value = false;
    }
}

function handleAssignment() {
    emit('assign', {
        departmentId: assignedDepartment.value,
        agentId: assignedAgent.value,
        conversationId: props.conversation.id
    });
}

const isAssigning = ref(false);
const assignmentError = ref('');
const assignmentSuccess = ref('');

const handleAssign = async (agentId) => {
    isAssigning.value = true;
    assignmentError.value = '';
    assignmentSuccess.value = '';

    try {
        await axios.post(`/helpdesk/conversations/${props.conversation.id}/assign-agent`, {
            agent_id: agentId
        });
        
        assignmentSuccess.value = 'Successfully assigned conversation';
        showAssignMenu.value = false;
        
        // Emit the assign event to refresh the parent
        emit('assign', {
            conversationId: props.conversation.id,
            agentId: agentId
        });
    } catch (error) {
        assignmentError.value = 'Failed to assign conversation';
        console.error('Assignment error:', error);
    } finally {
        isAssigning.value = false;
        // Hide success message after 3 seconds
        if (assignmentSuccess.value) {
            setTimeout(() => {
                assignmentSuccess.value = '';
            }, 3000);
        }
    }
};

const handleTransfer = (agentId) => {
    emit('transfer', {
        conversationId: props.conversation.id,
        agentId: agentId
    });
    showTransferMenu.value = false;
}

const showArchiveModal = ref(false);
const showSpamModal = ref(false);

// Add these refs for confirmation modals
const showUnspamModal = ref(false);

// Add unspam handler
const handleUnspam = () => {
    showUnspamModal.value = true;
};

const confirmUnspam = async () => {
    try {
        await axios.post(`/helpdesk/conversations/${props.conversation.id}/unspam`);
        showToast('Conversation removed from spam', 'success');
        router.visit('/helpdesk/support');
    } catch (error) {
        console.error('Error removing from spam:', error);
        showToast('Failed to remove from spam', 'error');
    } finally {
        showUnspamModal.value = false;
    }
};

// Update the spam handler to use confirmation
const handleSpam = async () => {
    showSpamModal.value = true;
};

const confirmSpam = async () => {
    try {
        // First, add the sender to spam contacts
        await axios.post('/helpdesk/spam', {
            type: 'email',
            value: props.conversation.user.email,
            reason: 'Marked as spam by agent'
        });

        // Then emit the event to handle UI updates
        emit('mark-spam', props.conversation.id);
        
        // Show success notification and redirect
        showToast('Conversation marked as spam', 'success');
        router.visit('/helpdesk/support');
        
    } catch (error) {
        console.error('Error marking as spam:', error);
        showToast('Failed to mark as spam', 'error');
    } finally {
        showSpamModal.value = false;
    }
};

// Update the archive handler to use confirmation
const handleArchive = () => {
    showArchiveModal.value = true;
};

const confirmArchive = async () => {
    try {
        const response = await axios.post(`/helpdesk/conversations/${props.conversation.id}/archive`);
        
        // Emit the archive event to update the UI
        emit('archive', props.conversation.id);
        
        // Show success notification and redirect
        showToast('Conversation archived successfully', 'success');
        router.visit('/helpdesk/support');
        
    } catch (error) {
        console.error('Error archiving conversation:', error);
        showToast('Failed to archive conversation', 'error');
    } finally {
        showArchiveModal.value = false;
    }
};

// Add new ref for unarchive modal
const showUnarchiveModal = ref(false);

// Add unarchive handlers
const handleUnarchive = () => {
    showUnarchiveModal.value = true;
};

const confirmUnarchive = async () => {
    try {
        await axios.post(`/helpdesk/conversations/${props.conversation.id}/unarchive`);
        showToast('Conversation unarchived successfully', 'success');
        router.visit('/helpdesk/support');
    } catch (error) {
        console.error('Error unarchiving conversation:', error);
        showToast('Failed to unarchive conversation', 'error');
    } finally {
        showUnarchiveModal.value = false;
    }
};

const showNotification = (message, type = 'success') => {
    // You can implement this based on your notification system
    console.log(`${type}: ${message}`);
};

function handleEscalate() {
    emit('escalate', props.conversation.id);
}

function toggleReplyMode() {
    showEmailReply.value = !showEmailReply.value;
}

async function handleEmailSend(emailData) {
    console.log('Email data before sending:', emailData); // Add this debug line
    
    if (isSending.value) return;
    
    isSending.value = true;
    try {
        const response = await axios.post(
            `/helpdesk/conversations/${props.conversation.id}/messages`,
            {
                content: emailData.content,
                type: 'email',
                subject: emailData.subject || `Re: ${props.conversation.subject}`,
                cc: emailData.cc,
                bcc: emailData.bcc,
                attachments: emailData.attachments
            }
        );

        // Add response logging
        console.log('Email send response:', response.data); // Add this debug line

        emit('send-message', response.data.data);
        showEmailReply.value = false;
        showToast('Email sent successfully', 'success');
    } catch (error) {
        console.error('Failed to send email:', error.response?.data || error);
        showToast('Failed to send email', 'error');
    } finally {
        isSending.value = false;
    }
}

// Add computed properties for conversation details
const assignedAgentDetails = computed(() => {
    return props.agents.find(a => a.id === props.conversation.agent_id);
});

const assignedDepartmentDetails = computed(() => {
    return props.departments.find(d => d.id === props.conversation.department_id);
});

const conversationStatus = computed(() => {
    const statusColors = {
        'new': 'bg-blue-100 text-blue-800',
        'open': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'resolved': 'bg-gray-100 text-gray-800',
        'closed': 'bg-gray-100 text-gray-800',
        'spam': 'bg-red-100 text-red-800'
    };
    return {
        color: statusColors[props.conversation.status] || '',
        label: props.conversation.status
    };
});

// Updated date formatter function
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        // Handle relative time strings
        if (typeof dateString === 'string' && dateString.includes('ago')) {
            return dateString;
        }

        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }

        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    } catch (e) {
        console.error('Date formatting error:', e);
        return 'N/A';
    }
};

// Add environment check
const isDevelopment = process.env.NODE_ENV === 'development';

</script>

<template>
    <div class="flex flex-col h-full"> <!-- Removed bg-red-500 -->
        <!-- Enhanced Header with Conversation Details -->
        <div class="border-b bg-white">
            <!-- Main header -->
            <div class="p-4">
                <div class="flex justify-between items-start">
                    <!-- Left side - Enhanced Conversation info -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-medium">{{ conversation.subject }}</h2>
                            <span :class="[
                                'px-2 py-0.5 text-xs rounded-full',
                                conversationStatus.color
                            ]">
                                {{ conversationStatus.label }}
                            </span>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <div class="flex items-center gap-1">
                                <Icon name="user" size="4" class="text-gray-400" />
                                {{ conversation.user.name }}
                            </div>
                            <div class="flex items-center gap-1">
                                <Icon name="email" size="4" class="text-gray-400" />
                                {{ conversation.user.email }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right side - Action buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Transfer & Escalate (for non-admin) -->
                        <div v-if="!isAdmin" class="flex items-center gap-2">
                            <div class="relative">
                                <button @click="showTransferMenu = !showTransferMenu"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-yellow-600 hover:bg-yellow-50 rounded-md">
                                    <Icon name="transfer" size="4" />
                                    Transfer
                                </button>
                                <div v-if="showTransferMenu"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                    <div class="py-1">
                                        <button v-for="agent in availableAgents"
                                                :key="agent.id"
                                                @click="handleTransfer(agent.id)"
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ agent.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button @click="handleEscalate"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-orange-600 hover:bg-orange-50 rounded-md">
                                <Icon name="escalate" size="4" />
                                Escalate
                            </button>
                        </div>

                        <!-- Admin Actions Group -->
                        <div class="flex items-center gap-2">
                            <!-- Assign (Admin only) -->
                            <div v-if="isAdmin" class="relative">
                                <button @click="showAssignMenu = !showAssignMenu"
                                        :disabled="isAssigning"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-md">
                                    <Icon name="assign" size="4" />
                                    Assign
                                    <span v-if="isAssigning" class="ml-2">
                                        <!-- Add a loading spinner here if needed -->
                                    </span>
                                </button>
                                
                                <!-- Assignment dropdown menu -->
                                <div v-if="showAssignMenu"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 py-1 border border-gray-200">
                                    <!-- Show assignment error if any -->
                                    <div v-if="assignmentError" class="px-4 py-2 text-sm text-red-600 bg-red-50">
                                        {{ assignmentError }}
                                    </div>
                                    
                                    <!-- Show success message if any -->
                                    <div v-if="assignmentSuccess" class="px-4 py-2 text-sm text-green-600 bg-green-50">
                                        {{ assignmentSuccess }}
                                    </div>
                                    
                                    <!-- Agent list -->
                                    <div class="max-h-48 overflow-y-auto">
                                        <button v-for="agent in agents"
                                                :key="agent.id"
                                                @click="handleAssign(agent.id)"
                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                                                :disabled="isAssigning">
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full"
                                                     :class="agent.status === 'online' ? 'bg-green-500' : 'bg-gray-300'">
                                                </div>
                                                {{ agent.name }}
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Only show Spam/Unspam button if admin -->
                            <button v-if="isAdmin"
                                    @click="conversation.status === 'spam' ? handleUnspam() : handleSpam()"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md"
                                    :class="conversation.status === 'spam' 
                                        ? 'text-green-600 hover:bg-green-50' 
                                        : 'text-red-600 hover:bg-red-50'">
                                <Icon :name="conversation.status === 'spam' ? 'check' : 'spam'" size="4" />
                                {{ conversation.status === 'spam' ? 'Unmark Spam' : 'Mark Spam' }}
                            </button>

                            <!-- Archive button -->
                            <button @click="['resolved', 'closed'].includes(conversation.status) ? handleUnarchive() : handleArchive()"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md"
                                    :class="['resolved', 'closed'].includes(conversation.status)
                                        ? 'text-green-600 hover:bg-green-50'
                                        : 'text-gray-700 hover:bg-gray-100'">
                                <Icon :name="['resolved', 'closed'].includes(conversation.status) ? 'inbox' : 'archive'" size="4" />
                                {{ ['resolved', 'closed'].includes(conversation.status) ? 'Unarchive' : 'Archive' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversation Details Bar -->
            <div class="px-4 py-2 bg-gray-50 flex items-center justify-between text-sm border-t">
                <div class="flex items-center gap-6">
                    <!-- Department Info -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Department:</span>
                        <span class="font-medium">
                            {{ assignedDepartmentDetails?.name || 'Unassigned' }}
                        </span>
                    </div>

                    <!-- Agent Info -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Agent:</span>
                        <span class="font-medium">
                            {{ assignedAgentDetails?.name || 'Unassigned' }}
                        </span>
                    </div>

                    <!-- Updated Created Date display -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">Created:</span>
                        <span class="font-medium">
                            {{ formatDate(conversation.created_at) }}
                            <span v-if="isDevelopment" class="text-xs text-gray-400">
                                (Raw: {{ conversation.created_at }})
                            </span>
                        </span>
                    </div>
                </div>

                <!-- Ticket ID -->
                <div class="flex items-center gap-2">
                    <span class="text-gray-500">Ticket ID:</span>
                    <span class="font-medium">#{{ conversation.id }}</span>
                </div>
            </div>
        </div>

        <!-- Update messages area with ref -->
        <div class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900" ref="messagesContainer">
            <div class="h-[calc(100% - 20px)]"> <!-- Reduced height by 20px -->
                <MessageBox v-for="message in conversation.messages"
                           :key="message.id"
                           :id="`message-${message.id}`"
                           :message="message"
                           :conversation="conversation" />
            </div>
        </div>

        <!-- Reply area stays fixed at bottom -->
        <div class="border-t bg-white dark:bg-gray-800 dark:border-gray-700 p-4"> <!-- Added p-4 -->
            <!-- Reply mode toggle -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-2">
                    <button @click="toggleReplyMode"
                            :class="[
                                'text-sm px-3 py-1.5 rounded-md transition-colors',
                                !showEmailReply 
                                    ? 'bg-indigo-100 text-indigo-700' 
                                    : 'text-gray-600 hover:text-gray-900'
                            ]">
                        Quick Reply
                    </button>
                    <button @click="toggleReplyMode"
                            :class="[
                                'text-sm px-3 py-1.5 rounded-md transition-colors',
                                showEmailReply 
                                    ? 'bg-indigo-100 text-indigo-700' 
                                    : 'text-gray-600 hover:text-gray-900'
                            ]">
                        Email Reply
                    </button>
                </div>
            </div>

            <!-- Quick reply input -->
            <div v-if="!showEmailReply" class="flex items-center gap-3 px-2"> <!-- Added px-2 -->
                <input v-model="message"
                       type="text"
                       class="flex-1 rounded-full border px-6 py-3 bg-white dark:bg-gray-700 
                              dark:border-gray-600 dark:text-gray-200"
                       placeholder="Type your message..."
                       :disabled="isSending"
                       @keyup.enter="sendMessage" />
                <button @click="sendMessage"
                        :disabled="isSending"
                        class="p-3 bg-indigo-600 text-white rounded-full hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <!-- Loading spinner when sending -->
                    <div v-if="isSending" class="animate-spin">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <!-- Send icon when not sending -->
                    <Icon v-else name="send" size="5" class="text-white" />
                </button>
            </div>

            <!-- Email reply editor -->
            <div v-else class="px-2"> <!-- Added px-2 -->
                <EmailEditor
                    :recipient="conversation.user.email"
                    :subject="'Re: ' + conversation.subject"
                    :signatures="signatures"
                    :disabled="isSending"
                    @send="handleEmailSend" />
            </div>
        </div>

        <!-- Add Confirmation Modals -->
        <ConfirmationModal
            :show="showArchiveModal"
            title="Archive Conversation"
            message="Are you sure you want to archive this conversation? It will be moved to the archived section."
            confirm-label="Archive"
            confirm-style="primary"
            @close="showArchiveModal = false"
            @confirm="confirmArchive"
        />

        <ConfirmationModal
            :show="showSpamModal"
            title="Mark as Spam"
            message="Are you sure you want to mark this conversation as spam? The sender's email will be added to the spam list and future messages will be filtered."
            confirm-label="Mark as Spam"
            confirm-style="danger"
            @close="showSpamModal = false"
            @confirm="confirmSpam"
        />

        <!-- Add unspam confirmation modal -->
        <ConfirmationModal
            :show="showUnspamModal"
            title="Remove from Spam"
            message="Are you sure you want to remove this conversation from spam? It will be marked as new and the sender will be removed from the spam list."
            confirm-label="Remove from Spam"
            confirm-style="primary"
            @close="showUnspamModal = false"
            @confirm="confirmUnspam"
        />

        <!-- Add unarchive confirmation modal -->
        <ConfirmationModal
            :show="showUnarchiveModal"
            title="Unarchive Conversation"
            message="Are you sure you want to unarchive this conversation? It will be moved back to active conversations."
            confirm-label="Unarchive"
            confirm-style="primary"
            @close="showUnarchiveModal = false"
            @confirm="confirmUnarchive"
        />
    </div>
</template>

<style scoped>
.bg-gray-50 {
    background-color: #F9FAFB;
}
.min-w-chat {
    min-width: 45rem;
}
.flex-1 {
    min-height: 0; /* This is important for proper scrolling */
}

/* Update chat input styles */
.chat-input {
    @apply px-4 py-3;
    border-top: 1px solid var(--ag-border-color);
    background: white;
}

/* Add smooth transition for input focus */
input {
    transition: all 0.2s ease-in-out;
}

input:focus {
    @apply ring-2 ring-indigo-500 ring-offset-2;
}

/* Add loading button styles */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
