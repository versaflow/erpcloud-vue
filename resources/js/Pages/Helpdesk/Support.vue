<script setup>
import { ref, computed, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FilterSidebar from '@/Components/Helpdesk/FilterSidebar.vue';
import ConversationList from '@/Components/Helpdesk/ConversationList.vue';
import ChatArea from '@/Components/Helpdesk/ChatArea.vue';
import UserInfoPanel from '@/Components/Helpdesk/UserInfoPanel.vue';

// Add debug logging
const props = defineProps({
    conversations: {
        type: Array,
        required: true,
        default: () => []
    },
    departments: {
        type: Array,
        required: true,
        default: () => []
    },
    agents: {
        type: Array,
        required: true,
        default: () => []
    },
    emailSources: {
        type: Array,
        required: true,
        default: () => []
    },
    signatures: { // Add signatures prop
        type: Array,
        required: true,
        default: () => []
    }
});

console.log('Support Component Props:', {
    departmentsCount: props.departments?.length,
    agentsCount: props.agents?.length,
    departments: props.departments,
    agents: props.agents
});

const page = usePage();

const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value.is_admin);
const selectedConversation = ref(null);

const currentFilter = ref('inbox');
const searchText = ref('');
const selectedAgent = ref('all');
const selectedDepartment = ref('all');

const filteredConversations = computed(() => {
    if (!props.conversations) return [];
    
    return props.conversations.filter(conv => {
        // Filter by search text
        if (searchText.value) {
            const searchLower = searchText.value.toLowerCase();
            const matchesSearch = 
                conv.subject?.toLowerCase().includes(searchLower) ||
                conv.user?.name?.toLowerCase().includes(searchLower) ||
                conv.user?.email?.toLowerCase().includes(searchLower);
            
            if (!matchesSearch) return false;
        }

        // Filter by agent
        if (selectedAgent.value !== 'all') {
            if (conv.agent_id !== selectedAgent.value) return false;
        }

        // Filter by department
        if (selectedDepartment.value !== 'all') {
            if (conv.department_id !== selectedDepartment.value) return false;
        }

        // Filter by status
        switch (currentFilter.value) {
            case 'inbox':
                return ['new', 'open', 'pending'].includes(conv.status);
            case 'archived':
                return ['resolved', 'closed'].includes(conv.status);
            case 'spam':
                return conv.status === 'spam';
            default:
                return true;
        }
    });
});

// Add computed property for user conversations
const userConversations = computed(() => {
    if (!selectedConversation.value?.user?.email) return [];
    
    return props.conversations.filter(conv => 
        conv.user.email === selectedConversation.value.user.email &&
        conv.id !== selectedConversation.value.id
    ).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

function handleFilterChange(filters) {
    searchText.value = filters.search;
    currentFilter.value = filters.filter;
    selectedAgent.value = filters.agent;
    selectedDepartment.value = filters.department;
    // Reset selected conversation when filter changes
    selectedConversation.value = null;
}

async function handleAssignment({ conversationId, agentId }) {
    try {
        const response = await axios.post(`/helpdesk/conversations/${conversationId}/assign`, {
            agent_id: agentId
        });
        // Update conversation in the list
        refreshConversations();
    } catch (error) {
        console.error('Failed to assign conversation:', error);
    }
}

async function handleTransfer({ conversationId, agentId }) {
    try {
        const response = await axios.post(`/helpdesk/conversations/${conversationId}/transfer`, {
            agent_id: agentId
        });
        refreshConversations();
    } catch (error) {
        console.error('Failed to transfer conversation:', error);
    }
}

async function handleSpam(conversationId) {
    try {
        const response = await axios.post(`/helpdesk/conversations/${conversationId}/spam`);
        refreshConversations();
    } catch (error) {
        console.error('Failed to mark as spam:', error);
    }
}

async function handleArchive(conversationId) {
    try {
        const response = await axios.post(`/helpdesk/conversations/${conversationId}/archive`);
        refreshConversations();
    } catch (error) {
        console.error('Failed to archive conversation:', error);
    }
}

async function handleEscalate(conversationId) {
    try {
        const response = await axios.post(`/helpdesk/conversations/${conversationId}/escalate`);
        refreshConversations();
    } catch (error) {
        console.error('Failed to escalate conversation:', error);
    }
}

function refreshConversations() {
    // Implement your refresh logic here
    // This could be a router.visit() call or custom refresh method
}

function handleSendMessage(message) {
    // Handle sending message
    // You can make an API call here
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Support" />

        <div class="p-4">
            <div class="max-w-[120rem] mx-auto">
                <div class="flex gap-5 h-[calc(100vh-100px)]">
                    <!-- Filter sidebar - made slimmer -->
                    <div class="w-64 bg-white rounded-lg shadow-sm flex flex-col">
                        <FilterSidebar
                            :departments="departments"
                            :email-sources="emailSources"
                            :agents="agents"
                            :is-admin="isAdmin"
                            @filter-change="handleFilterChange"
                        />
                    </div>

                    <!-- Conversation list - adjusted width -->
                    <div class="w-[26rem] bg-white rounded-lg shadow-sm">
                        <ConversationList
                            :conversations="filteredConversations"
                            :selected-conversation="selectedConversation"
                            @select="selectedConversation = $event"
                        />
                    </div>

                    <!-- Chat area -->
                    <div class="flex-1 bg-white rounded-lg shadow-sm">
                        <ChatArea
                            v-if="selectedConversation"
                            :conversation="selectedConversation"
                            :departments="departments"
                            :agents="agents"
                            :signatures="signatures"
                            @assign="handleAssignment"
                            @transfer="handleTransfer"
                            @mark-spam="handleSpam"
                            @archive="handleArchive"
                            @escalate="handleEscalate"
                            @send-message="handleSendMessage"
                        />
                        <div v-else class="flex items-center justify-center h-full text-gray-500">
                            Select a conversation to start chatting
                        </div>
                    </div>

                    <!-- User Info Panel -->
                    <Transition name="slide-left">
                        <UserInfoPanel
                            v-if="selectedConversation"
                            :support-user="selectedConversation.user"
                            :user-conversations="userConversations"
                        />
                    </Transition>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Remove slide animations since we're not toggling anymore */
.slide-left-enter-active,
.slide-left-leave-active {
    transition: all 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>
