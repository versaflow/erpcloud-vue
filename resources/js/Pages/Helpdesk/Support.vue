<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3'; // Add router here
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChatArea from '@/Components/Helpdesk/ChatArea.vue';
import { AgGridVue } from '@ag-grid-community/vue3';
import { ModuleRegistry } from '@ag-grid-community/core';
import { ClientSideRowModelModule } from '@ag-grid-community/client-side-row-model';
import { useToast } from '@/Composables/useToast'; 
import '@ag-grid-community/styles/ag-grid.css';
import '@ag-grid-community/styles/ag-theme-alpine.css';
import UserInfoPanel from '@/Components/Helpdesk/UserInfoPanel.vue';
import axios from 'axios';  // Make sure axios is imported
const { showToast } = useToast();

// Register AG Grid Modules - This is the key fix
ModuleRegistry.register(ClientSideRowModelModule);
// ModuleRegistry.register(AdvancedFilterModule); // Register the advanced filter module

const props = defineProps({
    conversations: {
        type: Array,
        required: true,
        default: () => []
    },
    departments: {
        type: Array,
        required: true
    },
    agents: {
        type: Array,
        required: true
    },
    signatures: {
        type: Array,
        required: true
    }
});

// Initialize user and department info first
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const userDepartmentId = computed(() => currentUser.value.department_id);
const isAdmin = computed(() => currentUser.value.is_admin);

// Initialize refs
const selectedConversation = ref(null);
const showChatArea = ref(false);
const allConversations = ref([]);
const activeTab = ref('active');
const gridApi = ref({
    active: null,
    archived: null,
    spam: null
});

// Filtered conversations logic
const filteredConversations = computed(() => {
    const conversations = allConversations.value || [];
    
    // Admin can see everything
    if (currentUser.value.is_admin) {
        return conversations;
    }

    // Agents can only see:
    // 1. Tickets assigned to them
    // 2. Unassigned tickets from their department
    // 3. Unassigned tickets with no department
    return conversations.filter(conv => {
        return (
            conv.agent_id === currentUser.value.id ||
            (conv.agent_id === null && conv.department_id === userDepartmentId.value) ||
            (conv.agent_id === null && conv.department_id === null)
        );
    });
});

// Status-based conversation filters
const activeConversations = computed(() => 
    filteredConversations.value.filter(conv => 
        ['new', 'open', 'pending', 'assigned'].includes(conv.status)
    )
);

const archivedConversations = computed(() => 
    filteredConversations.value.filter(conv => 
        ['resolved', 'closed'].includes(conv.status)
    )
);

const spamConversations = computed(() => 
    filteredConversations.value.filter(conv => 
        conv.status === 'spam'
    )
);

// Unread counts
const activeUnreadCount = computed(() => 
    activeConversations.value.reduce((total, conv) => 
        total + (parseInt(conv.unread_messages_count) || 0), 0)
);

const archivedUnreadCount = computed(() => 
    archivedConversations.value.reduce((total, conv) => 
        total + (parseInt(conv.unread_messages_count) || 0), 0)
);

const spamUnreadCount = computed(() => 
    spamConversations.value.reduce((total, conv) => 
        total + (parseInt(conv.unread_messages_count) || 0), 0)
);

// Tab configuration
const tabs = [
    { 
        id: 'active', 
        label: 'Active', 
        count: computed(() => activeConversations.value.length),
        unreadCount: computed(() => activeUnreadCount.value),
        color: 'text-green-600 bg-green-50 ring-green-500/10'
    },
    { 
        id: 'archived', 
        label: 'Solved',
        count: computed(() => archivedConversations.value.length),
        unreadCount: computed(() => archivedUnreadCount.value),
        color: 'text-gray-600 bg-gray-50 ring-gray-500/10'
    },
    { 
        id: 'spam', 
        label: 'Spam', 
        count: computed(() => spamConversations.value.length),
        unreadCount: computed(() => spamUnreadCount.value),
        color: 'text-red-600 bg-red-50 ring-red-500/10'
    }
];

// Permission check helper
const canAccessConversation = computed(() => (conversation) => {
    if (currentUser.value.is_admin) return true;
    
    return (
        conversation.agent_id === currentUser.value.id ||
        (conversation.agent_id === null && conversation.department_id === userDepartmentId.value) ||
        (conversation.agent_id === null && conversation.department_id === null)
    );
});

// Single onCellClicked implementation
const onCellClicked = async params => {
    if (!canAccessConversation.value(params.data)) {
        showToast('You do not have permission to access this conversation', 'error');
        return;
    }

    if (params.column.colDef.field === 'subject') {
        // Find the full conversation data from our local store
        const conversation = allConversations.value.find(c => c.id === params.data.id);
        if (!conversation) {
            showToast('Conversation data not found', 'error');
            return;
        }

        // Ensure all required properties are present
        selectedConversation.value = {
            ...conversation,
            messages: conversation.messages || [],
            user: conversation.user || {},
            department_id: conversation.department_id,
            agent_id: conversation.agent_id,
            status: conversation.status,
            subject: conversation.subject,
            created_at: conversation.created_at,
            updated_at: conversation.updated_at,
            is_priority: conversation.is_priority || false,
            unread_messages_count: conversation.unread_messages_count || 0
        };

        showChatArea.value = true;

        try {
            // Just mark as read without reloading
            await axios.post(`/helpdesk/conversations/${params.data.id}/read`);
            
            // Update unread count in local store
            const index = allConversations.value.findIndex(c => c.id === params.data.id);
            if (index !== -1) {
                allConversations.value[index].unread_messages_count = 0;
            }

            // Update the grid
            params.api.refreshCells({ force: true });
        } catch (error) {
            console.error('Failed to mark messages as read:', error);
        }

        // Update status if needed
        if (params.data.status === 'new') {
            try {
                await axios.post(`/helpdesk/conversations/${params.data.id}/status`, {
                    status: 'open'
                });
                // Update local state
                const index = allConversations.value.findIndex(c => c.id === params.data.id);
                if (index !== -1) {
                    allConversations.value[index].status = 'open';
                                    }
                params.api.refreshCells({ force: true });
            } catch (error) {
                console.error('Failed to update conversation status:', error);
            }
        }
    } else if (params.column.colId === 'actions') {
        const actionButton = params.event.target.closest('button[data-action]');
        if (actionButton) {
            const action = actionButton.getAttribute('data-action');
            handleAction(action, params.data);
        }
    }
};

// Add user and department info first
const user = computed(() => page.props.auth.user);


// Update height calculations
const headerHeight = '50px';
const marginSpacing = '5px'; // 4rem total (top + bottom)
const containerHeight = `99vh`;


// Update the chat container height to match
const chatContainerHeight = computed(() => 'calc(100vh - 0px)'); // Changed from 100px to 50px

// Add computed for previous conversations
const userPreviousConversations = computed(() => {
    if (!selectedConversation.value?.user?.email) return [];
    return props.conversations.filter(conv => 
        conv.user.email === selectedConversation.value.user.email &&
        conv.id !== selectedConversation.value.id
    ).sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

// Enhanced column definitions with better styling
const columnDefs = [
    {
        headerName: 'Status',
        field: 'status',
        width: 120,
        cellClass: 'ag-cell-center-items',
        filter: 'agSetColumnFilter',
        filterParams: {
            values: ['new', 'open', 'pending', 'resolved', 'closed', 'spam']
        },
        cellRenderer: params => {
            const statusColors = {
                'new': 'bg-blue-100 text-blue-800 border-blue-200',
                'open': 'bg-green-100 text-green-700 border-green-200',
                'pending': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'resolved': 'bg-gray-100 text-gray-800 border-gray-200',
                'closed': 'bg-gray-100 text-gray-800 border-gray-200',
                'spam': 'bg-red-100 text-red-700 border-red-200'
            };
            return `
                <div class="flex items-center h-full">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full ${statusColors[params.value]?.replace('bg-', 'bg-')}"></span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium border ${statusColors[params.value]}">
                            ${params.value}
                        </span>
                    </div>
                </div>
            `;
        }
    },
    { 
        headerName: 'Subject',
        width: 320, 
        field: 'subject',
        flex: 2,
        cellRenderer: params => `
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 mr-2 inline-block flex-shrink-0 ${params.data.unread_messages_count ? 'rounded-full bg-red-500' : ''}" aria-hidden="true"></span>
                <span class="font-medium text-gray-900 truncate">${params.value}</span>
                ${params.data.is_priority ? '<span class="text-red-500">⚡</span>' : ''}
            </div>
        `
    },
    { 
        headerName: 'Source',
        field: 'source',
        width: 170,
        cellClass: 'ag-cell-center-items single-line-cell',
        filter: 'agTextColumnFilter',
        filterParams: {
            filterOptions: ['contains', 'notContains', 'equals', 'notEqual'],
            defaultOption: 'contains'
        },
        cellRenderer: params => {
            const source = params.value || 'N/A';
            const toEmail = params.data.to_email;
            
            if (source.toLowerCase() === 'email' && toEmail) {
                return `
                    <div class="flex items-center gap-2 w-full">
                        <svg class="w-4 h-4 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="text-sm text-gray-600 truncate">${toEmail}</span>
                    </div>
                `;
            }
            
            return `
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">${source}</span>
                </div>
            `;
        }
    },
    { 
        headerName: 'Customer',
        field: 'user.name',
        width: 320,
        cellClass: 'ag-cell-center-items',
        cellRenderer: params => `
            <div class="flex items-center h-full gap-2">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-medium">
                    ${params.data.user.name.charAt(0)}
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">${params.data.user.name}</div>
                    <div class="text-xs text-gray-500">${params.data.user.email}</div>
                </div>
            </div>
        `
    },
    { 
        headerName: 'Department',
        field: 'department_id',
        width: 150,
        editable: params => isAdmin.value, // Conditionally set editable
        cellEditor: 'agSelectCellEditor', // Ensure this is set
        cellEditorParams: {
            values: [null, ...props.departments.map(d => d.id)],
            formatValue: value => {
                const dept = props.departments.find(d => d.id === value);
                return dept?.name || 'Unassigned';
            },
            valueListGap: 4,
            valueListMaxWidth: 200
        },
        valueFormatter: params => {
            const dept = props.departments.find(d => d.id === params.value);
            return dept?.name || 'Unassigned';
        },
        // Add filterValueGetter to convert ID to text for filtering
        filterValueGetter: params => {
            const dept = props.departments.find(d => d.id === params.data.department_id);
            return dept?.name || 'Unassigned';
        },
        // Change filter type to text
        filter: 'agTextColumnFilter',
        // Configure text filter parameters
        filterParams: {
            filterOptions: ['contains', 'notContains', 'equals', 'notEqual'],
            defaultOption: 'contains'
        },
        cellRenderer: params => {
            const dept = props.departments.find(d => d.id === params.value);
            return `<div class="truncate whitespace-nowrap">${dept ? dept.name : 'Unassigned'}</div>`;
        },
        cellClass: 'single-line-cell'
    },
    { 
        headerName: 'Agent',
        field: 'agent_id',
        width: 150,
        editable: params => isAdmin.value, // Conditionally set editable
        cellEditor: 'agSelectCellEditor', // Ensure this is set
        cellEditorParams: {
            values: [null, ...props.agents.map(a => a.id)],
            formatValue: value => {
                const agent = props.agents.find(a => a.id === value);
                return agent?.name || 'Unassigned';
            },
            valueListGap: 4,
            valueListMaxWidth: 200
        },
        valueFormatter: params => {
            const agent = props.agents.find(a => a.id === params.value);
            return agent?.name || 'Unassigned';
        },
        cellRenderer: params => {
            const agent = props.agents.find(a => a.id === params.value);
            return `<div class="truncate whitespace-nowrap">${agent ? agent.name : 'Unassigned'}</div>`;
        },
        cellClass: 'single-line-cell'
    },
    { 
        headerName: 'Last Updated',
        field: 'updated_at',
        width: 100,
        cellRenderer: params => {
            console.log(params.value);
            const date = new Date(params.value);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            if (days < 7) return `${days}d ago`;
            return params.value;
        }
    },
    {
        headerName: 'Actions',
        colId: 'actions', // Add this explicit colId
        width: 120,
        cellClass: 'ag-cell-center-items',
        cellRenderer: params => {
            const isArchivedStatus = ['resolved', 'closed'].includes(params.data.status);
            return `
                <div class="flex items-center justify-center gap-2">
                    ${(isAdmin.value || !params.data.agent_id || params.data.agent_id === currentUser.value.id) ? `
                        <button data-action="escalate" 
                                class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded-full transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </button>
                    ` : ''}

                    <button data-action="${isArchivedStatus ? 'unarchive' : 'archive'}"
                            class="p-1.5 text-gray-600 hover:bg-gray-50 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </button>
                </div>
            `;
        }
    }
];

// Update the cell value changed handler
const onCellValueChanged = async (params) => {
    // Check if user has permission to make changes
    if (!isAdmin.value) {
        showToast('You do not have permission to make this change', 'error');
        // Revert the change
        params.api.applyTransaction({
            update: [{
                ...params.data,
                [params.column.colId]: params.oldValue
            }]
        });
        return;
    }

    // Continue with existing assignment logic
    if (params.column.colId === 'department_id' || params.column.colId === 'agent_id') {
        const type = params.column.colId === 'department_id' ? 'department' : 'agent';
        try {
            const response = await axios.post(`/helpdesk/conversations/${params.data.id}/assign-${type}`, {
                [`${type}_id`]: params.newValue
            });

            // Create a new data object with the updated value
            const updatedData = {
                ...params.data,
                [params.column.colId]: params.newValue
            };

            // Apply the transaction to update the grid
            params.api.applyTransaction({
                update: [updatedData]
            });

            // Refresh the specific cell to reflect the change
            params.api.redrawRows({ rowNodes: [params.node] });

        } catch (error) {
            console.error(`Failed to assign ${type}:`, error);
            // Revert the change in the grid
            params.api.applyTransaction({
                update: [{
                    ...params.data,
                    [params.column.colId]: params.oldValue
                }]
            });
        }
    }
};

// Add status colors mapping
const statusBackgrounds = {
    new: '#F8FAFF',      // very light blue
    open: '#ffffff',     // white
    pending: '#FFFDF5',  // very light yellow
    resolved: '#F8FDF9', // very light green
    closed: '#F9FAFB',   // very light gray
    spam: '#FEF5F5'     // very light red
};

// Update grid options with better styling
const gridOptions = {
    pagination: true,
    paginationPageSize: 20,
    rowSelection: { type: 'single' },
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true,
        autoHeight: true,
        wrapText: true,
        filter: true,
        filterParams: {
            buttons: ['reset', 'apply'],
            closeOnApply: true
        },
        suppressKeyboardEvent: params => {
            // Allow enter key for editing
            if (params.event.key === 'Enter' && !params.editing) {
                return false;
            }
            return false;
        },
        cellClass: params => {
            if ((params.colDef.field === 'department_id' || params.colDef.field === 'agent_id') && !isAdmin.value) {
                return 'non-editable-cell';
            }
            return '';
        }
    },
    domLayout: 'normal',
    rowHeight: 48, // Fixed row height
    headerHeight: 48,
    rowClass: 'hover:bg-gray-50',
    getRowStyle: params => {
        const style = {};
        
        // Add background color based on status
        const bgColor = statusBackgrounds[params.data.status];
        if (bgColor) {
            style.backgroundColor = bgColor;
        }

        // Add priority border if needed
        if (params.data.is_priority) {
            style.borderLeft = '4px solid #F87171'; // red-400
        }

        return style;
    },
    enableAdvancedFilter: true,
    suppressMenuHide: true,
    onCellValueChanged,
    getRowId: params => params.data.id,
    getRowNodeId: data => data.id,  // Add this
    immutableData: true  // Add this
};

// Simplified grid ready handler
const handleGridReady = (params, gridType) => {
    gridApi.value[gridType] = params.api;
    console.log(`${gridType} Grid Ready`);
};

// Add reactive watcher for props.conversations
watch(() => props.conversations, (newConversations) => {
    allConversations.value = newConversations;
}, { deep: true });

// Add refresh implementation
const refreshConversations = async () => {
    try {
        const response = await axios.get(route('helpdesk.support'));
        allConversations.value = response.data.conversations;
        gridApi.value[activeTab.value]?.refreshCells({ force: true });
    } catch (error) {
        console.error('Failed to refresh conversations:', error);
    }
};

// Add periodic refresh (every 30 seconds)
onMounted(() => {
    allConversations.value = props.conversations;
    // // Set up refresh interval
    const refreshInterval = setInterval(async () => {
        try {
            const response = await axios.get(route('helpdesk.support'));
            if (response.data.conversations) {
                allConversations.value = response.data.conversations;
                gridApi.value[activeTab.value]?.refreshCells({ force: true });
            }
        } catch (error) {
            console.error('Failed to refresh conversations:', error);
        }
    }, 3000);

    // Clean up interval
    onUnmounted(() => clearInterval(refreshInterval));
});

// Back to grid handler
const handleBackToGrid = () => {
    showChatArea.value = false;
    selectedConversation.value = null;
};

// Fix the methods that were undefined
const handleAssignment = (data) => {
    console.log('Assigning:', data);
    // Implement assignment logic
};

const handleTransfer = (data) => {
    console.log('Transferring:', data);
    // Implement transfer logic
};

const handleSendMessage = (data) => {
    console.log('Sending message:', data);
    // Implement send message logic
};

// Add handler for agent assignment from grid
const handleAgentAssignment = (event, conversationId) => {
    const agentId = event.target.dataset.agentId;
    if (agentId) {
        handleAssignment({
            conversationId,
            agentId: parseInt(agentId)
        });
    }
};

// Add method to refresh the grid
const refreshGrid = () => {
    const api = gridApi.value[activeTab.value];
    if (api) {
        api.refreshCells();
    }
};

// Watch for changes in department/agent assignments
watch(() => props.conversations, refreshGrid, { deep: true });

// Fix the tab template to show counts correctly
const tabLabel = computed(() => tab => ({
    total: tab.count.value,
    unread: tab.unreadCount.value,
    hasUnread: tab.unreadCount.value > 0
}));
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Support" />

        <!-- Remove min-h-screen and update the main container -->
        <div class="bg-gray-100 h-full overflow-hidden" :style="{ height: containerHeight }">
            <div class="h-full mx-4 mt-2"> <!-- Added margins -->
                <!-- Grid View with Tabs -->
                <div v-if="!showChatArea" class="h-full flex flex-col bg-white rounded-lg shadow-sm"> <!-- Added styling -->
                    <!-- Grid Tabs -->
                    <div class="border-b border-gray-200">
                        <div class="flex space-x-6 px-6">
                            <button v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="[
                                        'flex items-center gap-2 py-3 px-4 border-b-2 text-sm font-medium relative',
                                        activeTab === tab.id
                                            ? 'border-indigo-500 text-indigo-600 bg-indigo-50/50'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50'
                                    ]">
                                <div class="flex items-center gap-2">
                                    {{ tab.label }}
                                    <span :class="[
                                        'rounded-full px-2.5 py-0.5 text-xs font-medium flex items-center gap-1',
                                        tab.color
                                    ]">
                                        {{ tabLabel(tab).total }}
                                        <template v-if="tabLabel(tab).hasUnread">
                                            <span class="inline-block w-[1px] h-3 bg-current opacity-25 mx-1"></span>
                                            <span class="font-bold text-red-500">{{ tabLabel(tab).unread }}</span>
                                        </template>
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Remove the custom filters toolbar -->

                    <!-- Grids Container -->
                    <div class="flex-1 bg-white rounded-b-lg"> <!-- Added rounded bottom corners -->
                        <!-- Active Grid -->
                        <div v-show="activeTab === 'active'" class="h-full w-full">
                                <AgGridVue
                                    v-bind="gridOptions"
                                    :columnDefs="columnDefs"
                                    :rowData="activeConversations"
                                    @grid-ready="params => handleGridReady(params, 'active')"
                                    @cell-clicked="onCellClicked"
                                    class="h-full ag-theme-alpine "
                                />
                            
                        </div>

                        <!-- Archived Grid -->
                        <div v-show="activeTab === 'archived'" class="h-full">
                            <div class="ag-theme-alpine h-full">
                                <AgGridVue
                                    v-bind="gridOptions"
                                    :columnDefs="columnDefs"
                                    :rowData="archivedConversations"
                                    @grid-ready="params => handleGridReady(params, 'archived')"
                                    @cell-clicked="onCellClicked"
                                    class="h-full"
                                />
                            </div>
                        </div>

                        <!-- Spam Grid -->
                        <div v-show="activeTab === 'spam'" class="h-full">
                            <div class="ag-theme-alpine h-full">
                                <AgGridVue
                                    v-bind="gridOptions"
                                    :columnDefs="columnDefs"
                                    :rowData="spamConversations"
                                    @grid-ready="params => handleGridReady(params, 'spam')"
                                    @cell-clicked="onCellClicked"
                                    class="h-full"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat View with User Info Panel -->
                <div v-else 
                     class="h-full flex bg-white rounded-lg shadow-sm" 
                     :style="{ height: chatContainerHeight }">
                    <!-- Back button and chat area -->
                    <div class="flex-1 p-4 flex flex-col h-full overflow-hidden">
                        <div class="mb-4">
                            <button @click="handleBackToGrid"
                                    class="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back to Conversations
                            </button>
                        </div>
                        
                        <div class="flex-1 bg-white rounded-lg shadow-sm overflow-hidden">
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
                        </div>
                    </div>

                    <!-- Update UserInfoPanel to include previous conversations -->
                    <div class="w-80 border-l mr-4">
                        <UserInfoPanel
                            v-if="selectedConversation"
                            :support-user="selectedConversation.user"
                            :user-conversations="userPreviousConversations"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.action-btn {
    @apply px-2 py-1 text-xs rounded;
}
.action-btn.assign {
    @apply bg-blue-100 text-blue-800 hover:bg-blue-200;
}
.action-btn.escalate {
    @apply bg-yellow-100 text-yellow-800 hover:bg-yellow-200;
}
.action-btn.archive {
    @apply bg-gray-100 text-gray-800 hover:bg-gray-200;
}

/* Custom AG Grid styling */
:deep(.ag-theme-alpine) {
    --ag-header-height: 40px;
    --ag-header-background-color: #f9fafb;
    --ag-header-foreground-color: #374151;
    --ag-header-cell-hover-background-color: #f3f4f6;
    --ag-row-hover-color: #f3f4f6;
    --ag-pagination-padding: 1rem;
    height: 100% !important;
}

:deep(.ag-theme-alpine .ag-ping-panel) {
    border-top: 1px solid var(--ag-border-color);
    padding: var(--ag-pagination-padding);
    font-size: 0.875rem;
}

/* Add more specific grid styles */
:deep(.ag-theme-alpine) {
    --ag-header-height: 40px;
    --ag-row-height: 50px;
    --ag-header-background-color: #f9fafb;
    --ag-odd-row-background-color: #ffffff;
    --ag-header-foreground-color: #374151;
    --ag-foreground-color: #374151;
    --ag-secondary-foreground-color: #6b7280;
    --ag-alpine-active-color: #4f46e5;
    height: 100%;
    width: 100%;
    display: flex;
    flex-direction: column;
    min-height: 500px; /* Minimum height fallback */
    border-radius: 0 0 0.5rem 0.5rem; /* Match container's rounded corners */
    overflow: hidden; /* Ensure content respects rounded corners */
    min-width: 1200px; /* Minimum grid width */
}

/* Ensure the grid root element takes full height */
:deep(.ag-root) {
    flex: 1;
}

:deep(.ag-root-wrapper) {
    height: 100% !important;
}

/* Ensure proper display of the pagination panel */
:deep(.ag-theme-alpine .ag-ping-panel) {
    border-top: 1px solid var(--ag-border-color);
    padding: var(--ag-pagination-padding);
    font-size: 0.875rem;
    position: sticky;
    bottom: 0;
    background: white;
}

/* Ensure chat area scrolls properly */
:deep(.chat-messages) {
    overflow-y: auto;
    flex: 1;
}

:deep(.chat-input) {
    border-top: 1px solid var(--ag-border-color);
    background: white;
    padding: 1rem;
}

/* Add tab transition styles */
.tab-transition-enter-active,
.tab-transition-leave-active {
    transition: opacity 0.2s ease;
}

.tab-transition-enter-from,
.tab-transition-leave-to {
    opacity: 0;
}

/* Add styles for agent assignment dropdown */
.assign-dropdown {
    position: relative;
    display: inline-block;
}

.assign-dropdown:hover .assign-menu {
    display: block;
}

.assign-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 10;
    min-width: 160px;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.assign-option {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.assign-option:hover {
    background-color: #f3f4f6;
}

/* Update grid styles for better appearance */
:deep(.ag-theme-alpine) {
    @apply rounded-lg border border-gray-200;
    --ag-border-color: theme('colors.gray.200');
    --ag-row-border-color: theme('colors.gray.100');
    --ag-cell-horizontal-padding: theme('spacing.4');
    --ag-selected-row-background-color: theme('colors.indigo.50');
}

:deep(.ag-row) {
    @apply transition-colors;
}

:deep(.ag-row-hover) {
    @apply bg-gray-50;
}

/* Update active tab indicator */
.router-link-active,
.router-link-exact-active {
    position: relative;
}

.router-link-active::after,
.router-link-exact-active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: theme('colors.indigo.500');
}

/* Add hover effect for tabs */
button:hover .rounded-full {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}

/* Add these new styles for cell alignment */
:deep(.ag-cell-center-items) {
    display: flex !important;
    align-items: center !important;
    padding: 0.5rem 1rem !important;
}

:deep(.ag-cell-center-items > div) {
    width: 100%;
}

/* Update grid row styles */
:deep(.ag-row) {
    @apply transition-colors;
    display: flex;
    align-items: center;
    height: 48px !important;
    max-height: 48px !important;
}

/* Add styles for new action buttons */
:deep(.ag-cell) button {
    transition: all 0.2s ease;
}

:deep(.ag-cell) button:hover {
    transform: translateY(-1px);
}

:deep(.ag-cell) button:active {
    transform: translateY(0);
}

/* Update action button styles */
:deep(.ag-cell) button {
    transition: all 0.15s ease;
}

:deep(.ag-cell) button:hover {
    transform: scale(1.1);
}

:deep(.ag-cell) button:active {
    transform: scale(0.95);
}

/* Add line-clamp utility if not already included in your Tailwind config */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-height: 2.5rem; /* Approximately 2 lines of text */
}

/* Add styles for filters */
select {
    @apply min-w-[150px] py-1;
}

/* Add these AG Grid filter styling improvements */
:deep(.ag-theme-alpine) {
    --ag-header-height: 48px; /* Increased to accommodate filter icons */
    --ag-filter-tool-panel-width: 250px;
}

:deep(.ag-filter-panel) {
    @apply bg-white border border-gray-200 shadow-lg rounded-lg;
}

:deep(.ag-filter-toolpanel-header) {
    @apply bg-gray-50 p-3 border-b border-gray-200;
}

:deep(.ag-filter-select) {
    @apply rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}

/* Add AG Grid popup fix */
:deep(.ag-popup) {
    height: 0 !important;
    min-height: 0 !important;
}

.ag-popup {
    height: 0 !important;
    min-height: 0 !important;
}

/* Remove existing line-clamp styles and replace with truncate */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Add specific hover effect for rows that preserves background color */
:deep(.ag-row-hover) {
    filter: brightness(0.95);
}

/* Update row transition */
:deep(.ag-row) {
    transition: background-color 0.2s ease-in-out;
}

/* Add visual indication for non-editable cells */
:deep(.non-editable-cell) {
    background-color: #f9fafb;
    cursor: not-allowed;
}

.nowrap-cell {
    white-space: nowrap;
}

/* Add styles for single line cells */
:deep(.single-line-cell) {
    @apply overflow-hidden;
}

:deep(.single-line-cell > div) {
    @apply truncate whitespace-nowrap overflow-hidden text-ellipsis max-w-full;
    text-overflow: ellipsis;
}
</style>
