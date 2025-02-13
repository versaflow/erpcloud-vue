<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icons/Index.vue';
import MessageBox from './MessageBox.vue';
import EmailEditor from './EmailEditor.vue';

const props = defineProps({
    conversation: {
        type: Object,
        required: true
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

function sendMessage() {
    if (!message.value.trim()) return;
    // Emit message event
    emit('send-message', {
        content: message.value,
        conversationId: props.conversation.id
    });
    message.value = '';
}

function handleAssignment() {
    emit('assign', {
        departmentId: assignedDepartment.value,
        agentId: assignedAgent.value,
        conversationId: props.conversation.id
    });
}

function handleAssign(agentId) {
    emit('assign', {
        conversationId: props.conversation.id,
        agentId: agentId
    });
    showAssignMenu.value = false;
}

function handleTransfer(agentId) {
    emit('transfer', {
        conversationId: props.conversation.id,
        agentId: agentId
    });
    showTransferMenu.value = false;
}

function handleSpam() {
    emit('mark-spam', props.conversation.id);
}

function handleArchive() {
    emit('archive', props.conversation.id);
}

function handleEscalate() {
    emit('escalate', props.conversation.id);
}

function toggleReplyMode() {
    showEmailReply.value = !showEmailReply.value;
}

function handleEmailSend(emailData) {
    emit('send-message', {
        ...emailData,
        conversationId: props.conversation.id,
        type: 'email'
    });
    showEmailReply.value = false;
}
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Header with actions -->
        <div class="p-4 border-b bg-white">
            <div class="flex justify-between items-start">
                <!-- Left side - Conversation info -->
                <div>
                    <h2 class="text-lg font-medium">{{ conversation.subject }}</h2>
                    <p class="text-sm text-gray-500">{{ conversation.user.email }}</p>
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
                                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-md">
                                <Icon name="assign" size="4" />
                                Assign
                            </button>
                            <div v-if="showAssignMenu"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                <div class="py-1">
                                    <button v-for="agent in agents"
                                            :key="agent.id"
                                            @click="handleAssign(agent.id)"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        {{ agent.name }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Spam button (Admin only) -->
                        <button v-if="isAdmin"
                                @click="handleSpam"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
                            <Icon name="spam" size="4" />
                            Spam
                        </button>

                        <!-- Archive button -->
                        <button @click="handleArchive"
                                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                            <Icon name="archive" size="4" />
                            Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages area with dark theme -->
        <div class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-900">
            <MessageBox v-for="(message, index) in conversation.messages"
                       :key="message.id"
                       :message="message"
                       :is-first="index === 0" />
        </div>

        <!-- Reply area -->
        <div class="p-4 border-t bg-white dark:bg-gray-800 dark:border-gray-700">
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
            <div v-if="!showEmailReply" class="flex items-center gap-3">
                <input v-model="message"
                       type="text"
                       class="flex-1 rounded-full border px-4 py-2 bg-white dark:bg-gray-700 
                              dark:border-gray-600 dark:text-gray-200"
                       placeholder="Type your message..."
                       @keyup.enter="sendMessage" />
                <button @click="sendMessage"
                        class="p-2 bg-indigo-600 text-white rounded-full hover:bg-indigo-700">
                    <Icon name="send" size="5" class="text-white" />
                </button>
            </div>

            <!-- Email reply editor -->
            <EmailEditor v-else
                        :recipient="conversation.user.email"
                        :signatures="signatures"
                        @send="handleEmailSend" />
        </div>
    </div>
</template>

<style scoped>
.bg-gray-50 {
    background-color: #F9FAFB;
}
.min-w-chat {
    min-width: 45rem;
}
</style>
