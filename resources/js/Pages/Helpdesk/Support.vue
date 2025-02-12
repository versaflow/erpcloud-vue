<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const isShowUserChat = ref(false);
const isShowChatMenu = ref(false);
const loginUser = ref({
  id: 0,
  name: 'Alon Smith',
  path: 'profile-34.jpeg',
  designation: 'Software Developer',
});
const contactList = ref([
  {
    userId: 1,
    name: 'Nia Hillyer',
    path: 'profile-16.jpeg',
    time: '2:09 PM',
    preview: 'How do you do?',
    messages: [
      {
        fromUserId: 0,
        toUserId: 1,
        text: 'Hi, I am back from vacation',
      },
      {
        fromUserId: 0,
        toUserId: 1,
        text: 'How are you?',
      },
      {
        fromUserId: 1,
        toUserId: 0,
        text: 'Welcom Back',
      },
      {
        fromUserId: 1,
        toUserId: 0,
        text: 'I am all well',
      },
      {
        fromUserId: 0,
        toUserId: 1,
        text: 'Coffee?',
      },
    ],
    active: true,
  },
  // ... rest of the contact list
]);

const searchUser = ref('');
const textMessage = ref('');
const selectedUser = ref(null);

const selectedFilter = ref('inbox');
const selectedDepartment = ref('all');

// Add new email sources data
const emailSources = ref([
  { id: 'support', label: 'support@company.com', count: 15 },
  { id: 'sales', label: 'sales@company.com', count: 8 },
  { id: 'info', label: 'info@company.com', count: 5 },
  { id: 'billing', label: 'billing@company.com', count: 3 }
]);

const selectedEmailSource = ref('all');

const filterOptions = [
  { id: 'inbox', label: 'Inbox', count: 12 },
  { id: 'archived', label: 'Archived', count: 5 },
  { id: 'spam', label: 'Spam', count: 2 }
];

const departments = [
  { id: 'all', name: 'All Departments' },
  { id: 'tech', name: 'Technical Support' },
  { id: 'billing', name: 'Billing' },
  { id: 'sales', name: 'Sales' },
  { id: 'general', name: 'General Inquiry' }
];

// Update searchUsers computed to include email source filtering
const searchUsers = computed(() => {
  let filteredUsers = contactList.value;
  
  // Add email source filtering
  if (selectedEmailSource.value !== 'all') {
    filteredUsers = filteredUsers.filter(d => d.emailSource === selectedEmailSource.value);
  }
  
  // Filter by search text
  if (searchUser.value) {
    filteredUsers = filteredUsers.filter(d => 
      d.name.toLowerCase().includes(searchUser.value.toLowerCase())
    );
  }

  // Filter by ticket state
  filteredUsers = filteredUsers.filter(d => {
    switch (selectedFilter.value) {
      case 'archived':
        return d.archived;
      case 'spam':
        return d.spam;
      default:
        return !d.archived && !d.spam;
    }
  });

  // Filter by department
  if (selectedDepartment.value !== 'all') {
    filteredUsers = filteredUsers.filter(d => d.department === selectedDepartment.value);
  }

  return filteredUsers;
});

const selectUser = (user) => {
  selectedUser.value = user;
  isShowUserChat.value = true;
  scrollToBottom();
  isShowChatMenu.value = false;
};

const sendMessage = () => {
  if (textMessage.value.trim()) {
    const user = contactList.value.find((d) => d.userId === selectedUser.value.userId);
    user.messages.push({
      fromUserId: selectedUser.value.userId,
      toUserId: 0,
      text: textMessage.value,
      time: 'Just now',
    });
    textMessage.value = '';
    scrollToBottom();
  }
};

const scrollToBottom = () => {
  if (isShowUserChat.value) {
    setTimeout(() => {
      const element = document.querySelector('.chat-conversation-box');
      element.behavior = 'smooth';
      element.scrollTop = element.scrollHeight;
    });
  }
};

const ticketStatus = ref('open');
const ticketPriority = ref('medium');

const supportActions = [
  { icon: 'archive', label: 'Archive Ticket' },
  { icon: 'escalate', label: 'Escalate' },
  { icon: 'transfer', label: 'Transfer' },
  { icon: 'close', label: 'Close Ticket' }
];

// Add new user details data
const userDetails = computed(() => selectedUser.value ? {
  email: 'customer@example.com',
  phone: '+1 (555) 123-4567',
  company: 'Acme Corp',
  location: 'New York, USA',
  timezone: 'EST (UTC-5)',
  joinedDate: '2023-01-15',
  ticketHistory: [
    {
      id: '#TK-1234',
      subject: 'Login Issues',
      status: 'closed',
      date: '2023-12-01',
      department: 'Technical Support'
    },
    {
      id: '#TK-1235',
      subject: 'Billing Question',
      status: 'resolved',
      date: '2023-12-15',
      department: 'Billing'
    },
    {
      id: '#TK-1236',
      subject: 'Feature Request',
      status: 'open',
      date: '2024-01-05',
      department: 'Product'
    }
  ],
  notes: 'Premium customer, prioritize responses',
  tags: ['VIP', 'Enterprise', 'Priority']
} : null);

// Add form data
const ticketForm = ref({
  subject: '',
  emailSource: 'support',
  priority: 'medium',
  message: '',
  attachments: []
});

const showNewTicketForm = ref(false);

const createNewTicket = () => {
  // Handle ticket creation logic here
  console.log('Creating new ticket:', ticketForm.value);
  showNewTicketForm.value = false;
  // Reset form
  ticketForm.value = {
    subject: '',
    emailSource: 'support',
    priority: 'medium',
    message: '',
    attachments: []
  };
};

// Add agent list data
const agents = ref([
  { id: 1, name: 'John Smith', department: 'tech', status: 'online' },
  { id: 2, name: 'Sarah Johnson', department: 'billing', status: 'busy' },
  { id: 3, name: 'Mike Wilson', department: 'sales', status: 'offline' },
  { id: 4, name: 'Lisa Brown', department: 'tech', status: 'online' }
]);

const currentAgent = ref({
  id: 1,
  name: 'John Smith',
  department: 'tech',
  status: 'online',
  avatar: 'profile-34.jpeg'
});

const assignedAgent = ref(null);
const assignedDepartment = ref(null);

const handleAssignment = () => {
  // Handle assignment logic here
  console.log('Assigned to:', { agent: assignedAgent.value, department: assignedDepartment.value });
};
</script>

<template>
  <Head title="Support" />

  <AuthenticatedLayout>
    <!-- Header section - Updated -->
    <header class="bg-white shadow">
      <div class="max-w-[120rem] mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">Support Center</h2>

          <div class="flex items-center gap-6">
        <!-- Agent Info -->
        <div class="flex items-center gap-3 pr-6 border-r">
          <img :src="`/assets/images/${currentAgent.avatar}`" 
           class="w-10 h-10 rounded-full object-cover border-2" 
           :class="{
             'border-green-400': currentAgent.status === 'online',
             'border-yellow-400': currentAgent.status === 'busy',
             'border-gray-300': currentAgent.status === 'offline'
           }" />
          <div>
            <h3 class="font-medium text-sm">{{ currentAgent.name }}</h3>
            <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full" 
            :class="{
              'bg-green-500': currentAgent.status === 'online',
              'bg-yellow-500': currentAgent.status === 'busy',
              'bg-gray-400': currentAgent.status === 'offline'
            }">
          </span>
          <span class="text-xs text-gray-600">{{ currentAgent.status }}</span>
            </div>
          </div>
        </div>

        <!-- Support Center Title -->
          </div>

          <div class="flex items-center gap-4">
        <span class="px-3 py-1 rounded-full text-sm" :class="{
          'bg-green-100 text-green-800': ticketStatus === 'open',
          'bg-yellow-100 text-yellow-800': ticketStatus === 'pending',
          'bg-red-100 text-red-800': ticketStatus === 'closed'
        }">
          {{ ticketStatus.charAt(0).toUpperCase() + ticketStatus.slice(1) }}
        </span>
        <button @click="showNewTicketForm = true" 
            class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark">
          New Ticket
        </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="p-6">
      <div class="max-w-[100rem] mx-auto">
        <div class="flex gap-5 relative h-[calc(100vh_-_150px)]">
          
          <!-- Left sidebar -->
          <div class="w-80 bg-white rounded-lg shadow-sm flex flex-col">
            <!-- Search and filters -->
            <div class="p-4 space-y-4">
              <!-- Search input -->
              <div class="relative">
                <input type="text" 
                       class="w-full pl-10 pr-4 py-2 rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary"
                       placeholder="Search tickets..." 
                       v-model="searchUser" />
                <svg class="w-5 h-5 absolute left-3 top-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>

              <!-- Department filter -->
              <div class="relative">
                <select v-model="selectedDepartment"
                        class="w-full pl-3 pr-10 py-2 rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary appearance-none">
                  <option v-for="dept in departments" 
                          :key="dept.id" 
                          :value="dept.id">
                    {{ dept.name }}
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              <!-- Add email source filter below department filter -->
              <div class="relative">
                <select v-model="selectedEmailSource"
                        class="w-full pl-3 pr-10 py-2 rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary appearance-none">
                  <option value="all">All Email Sources</option>
                  <option v-for="source in emailSources" 
                          :key="source.id" 
                          :value="source.id">
                    {{ source.label }} ({{ source.count }})
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>

              <!-- Ticket state filters -->
              <div class="space-y-1">
                <button v-for="filter in filterOptions"
                        :key="filter.id"
                        @click="selectedFilter = filter.id"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg transition-colors"
                        :class="selectedFilter === filter.id ? 
                          'bg-primary text-white' : 
                          'hover:bg-gray-100 text-gray-700'">
                  <span class="flex items-center gap-2">
                    <svg v-if="filter.id === 'inbox'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <svg v-if="filter.id === 'archived'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    <svg v-if="filter.id === 'spam'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ filter.label }}
                  </span>
                  <span class="bg-opacity-20 px-2 py-1 rounded-full text-sm"
                        :class="selectedFilter === filter.id ? 'bg-white' : 'bg-gray-100'">
                    {{ filter.count }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Contact list -->
            <div class="flex-1 overflow-y-auto border-t">
              <div v-for="person in searchUsers" 
                   :key="person.userId"
                   @click="selectUser(person)"
                   class="p-4 hover:bg-gray-50 cursor-pointer flex items-center gap-3"
                   :class="{ 'bg-gray-50': selectedUser?.userId === person.userId }">
                <img :src="`/assets/images/${person.path}`" class="w-10 h-10 rounded-full object-cover" />
                <div class="flex-1 min-w-0">
                  <div class="flex justify-between items-start">
                    <h3 class="font-medium truncate">{{ person.name }}</h3>
                    <span class="text-xs text-gray-500">{{ person.time }}</span>
                  </div>
                  <p class="text-sm text-gray-500 truncate">{{ person.preview }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Main chat area - update width class -->
          <div class="flex-1 bg-white rounded-lg shadow-sm flex flex-col">
            <template v-if="isShowUserChat && selectedUser">
              <!-- Chat header - Simplified -->
              <div class="p-4 border-b flex flex-col gap-4">
                <!-- Ticket details and assignment -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <img :src="`/assets/images/${selectedUser.path}`" class="w-10 h-10 rounded-full object-cover" />
                    <div>
                      <h3 class="font-semibold">{{ selectedUser.name }}</h3>
                      <p class="text-sm text-gray-500">Ticket #{{ Math.floor(Math.random() * 10000) }}</p>
                    </div>
                  </div>
                  
                  <!-- Assignment controls -->
                  <div class="flex items-center gap-4">
                    <!-- Department Assignment -->
                    <div class="relative">
                      <select v-model="assignedDepartment"
                              class="pl-3 pr-8 py-1.5 rounded-lg border text-sm focus:border-primary focus:ring-1 focus:ring-primary appearance-none">
                        <option value="">Assign Department</option>
                        <option v-for="dept in departments" 
                                :key="dept.id" 
                                :value="dept.id">
                          {{ dept.name }}
                        </option>
                      </select>
                      <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                    </div>

                    <!-- Agent Assignment -->
                    <div class="relative">
                      <select v-model="assignedAgent"
                              class="pl-3 pr-8 py-1.5 rounded-lg border text-sm focus:border-primary focus:ring-1 focus:ring-primary appearance-none">
                        <option value="">Assign Agent</option>
                        <option v-for="agent in agents" 
                                :key="agent.id" 
                                :value="agent.id"
                                :disabled="agent.status === 'offline'">
                          {{ agent.name }} 
                          {{ agent.status === 'offline' ? '(Offline)' : agent.status === 'busy' ? '(Busy)' : '' }}
                        </option>
                      </select>
                      <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                    </div>

                    <!-- Assign Button -->
                    <button @click="handleAssignment"
                            class="px-3 py-1.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-dark"
                            :disabled="!assignedAgent && !assignedDepartment">
                      Assign
                    </button>
                  </div>
                </div>

                <!-- Ticket actions -->
                <div class="flex items-center justify-end gap-3">
                  <button v-for="action in supportActions" 
                          :key="action.label"
                          class="p-2 hover:bg-gray-100 rounded-lg text-gray-600 hover:text-primary">
                    <span class="text-sm">{{ action.label }}</span>
                  </button>
                </div>
              </div>

              <!-- Chat messages -->
              <div class="flex-1 overflow-y-auto p-4">
                <div v-for="(message, index) in selectedUser.messages" 
                     :key="index"
                     class="mb-4"
                     :class="{ 'flex justify-end': message.fromUserId === loginUser.id }">
                  <div class="flex items-start gap-3 max-w-[70%]"
                       :class="{ 'flex-row-reverse': message.fromUserId === loginUser.id }">
                    <img :src="`/assets/images/${message.fromUserId === loginUser.id ? loginUser.path : selectedUser.path}`" 
                         class="w-8 h-8 rounded-full object-cover" />
                    <div>
                      <div class="p-3 rounded-lg"
                           :class="message.fromUserId === loginUser.id ? 
                             'bg-primary text-white rounded-br-none' : 
                             'bg-gray-100 rounded-bl-none'">
                        {{ message.text }}
                      </div>
                      <span class="text-xs text-gray-500 mt-1 block">
                        {{ message.time || '5h ago' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Chat input -->
              <div class="p-4 border-t">
                <div class="flex items-center gap-3">
                  <button class="p-2 hover:bg-gray-100 rounded-full">
                    <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                  </button>
                  <input type="text" 
                         class="flex-1 border rounded-full px-4 py-2 focus:border-primary focus:ring-1 focus:ring-primary"
                         placeholder="Type your message..."
                         v-model="textMessage"
                         @keyup.enter="sendMessage" />
                  <button class="bg-primary text-white p-2 rounded-full hover:bg-primary-dark"
                          @click="sendMessage">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>

            <!-- Empty state -->
            <template v-else>
              <div class="flex-1 flex items-center justify-center">
                <div class="text-center">
                  <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                  <h3 class="text-xl font-semibold text-gray-700 mb-2">Select a conversation</h3>
                  <p class="text-gray-500">Choose a support ticket to start chatting</p>
                </div>
              </div>
            </template>
          </div>

          <!-- New right sidebar for user details -->
          <div v-if="selectedUser" class="w-80 bg-white rounded-lg shadow-sm flex flex-col overflow-hidden">
            <!-- User Profile Header -->
            <div class="p-4 border-b bg-gray-50">
              <h3 class="font-semibold text-lg mb-4">Customer Details</h3>
              <div class="flex items-center gap-3">
                <img :src="`/assets/images/${selectedUser.path}`" class="w-16 h-16 rounded-full object-cover" />
                <div>
                  <h4 class="font-medium">{{ selectedUser.name }}</h4>
                  <p class="text-sm text-gray-500">{{ userDetails.company }}</p>
                  <span class="inline-block px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 mt-1">
                    Active Customer
                  </span>
                </div>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="p-4 border-b">
              <h4 class="font-medium mb-3">Contact Information</h4>
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span>{{ userDetails.email }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span>{{ userDetails.phone }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>{{ userDetails.location }}</span>
                </div>
              </div>
            </div>

            <!-- Customer Tags -->
            <div class="p-4 border-b">
              <h4 class="font-medium mb-3">Tags</h4>
              <div class="flex flex-wrap gap-2">
                <span v-for="tag in userDetails.tags" 
                      :key="tag"
                      class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">
                  {{ tag }}
                </span>
              </div>
            </div>

            <!-- Ticket History -->
            <div class="flex-1 overflow-y-auto p-4">
              <h4 class="font-medium mb-3">Previous Tickets</h4>
              <div class="space-y-3">
                <div v-for="ticket in userDetails.ticketHistory" 
                     :key="ticket.id" 
                     class="p-3 bg-gray-50 rounded-lg">
                  <div class="flex justify-between items-start">
                    <span class="text-sm font-medium">{{ ticket.id }}</span>
                    <span class="px-2 py-1 text-xs rounded-full"
                          :class="{
                            'bg-green-100 text-green-800': ticket.status === 'open',
                            'bg-gray-100 text-gray-800': ticket.status === 'closed',
                            'bg-blue-100 text-blue-800': ticket.status === 'resolved'
                          }">
                      {{ ticket.status }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ ticket.subject }}</p>
                  <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
                    <span>{{ ticket.department }}</span>
                    <span>{{ ticket.date }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes Section -->
            <div class="p-4 border-t bg-gray-50">
              <h4 class="font-medium mb-2">Notes</h4>
              <p class="text-sm text-gray-600">{{ userDetails.notes }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </AuthenticatedLayout>

  <!-- New Ticket Modal -->
  <div v-if="showNewTicketForm" 
       class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-xl font-semibold">Create New Support Ticket</h2>
          <button @click="showNewTicketForm = false"
                  class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="createNewTicket" class="space-y-4">
          <!-- Subject -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
            <input type="text" 
                   v-model="ticketForm.subject"
                   class="w-full rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary"
                   required />
          </div>

          <!-- Email Source -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Receiving Email</label>
            <select v-model="ticketForm.emailSource"
                    class="w-full rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary">
              <option v-for="source in emailSources" 
                      :key="source.id" 
                      :value="source.id">
                {{ source.label }}
              </option>
            </select>
          </div>

          <!-- Priority -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
            <select v-model="ticketForm.priority"
                    class="w-full rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          <!-- Message -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
            <textarea v-model="ticketForm.message"
                      rows="4"
                      class="w-full rounded-lg border focus:border-primary focus:ring-1 focus:ring-primary"
                      required></textarea>
          </div>

          <!-- Attachments -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
            <div class="border-2 border-dashed rounded-lg p-4 text-center">
              <input type="file" 
                     multiple 
                     class="hidden" 
                     @change="ticketForm.attachments = $event.target.files" />
              <button type="button" 
                      class="text-primary hover:text-primary-dark"
                      @click="$event.target.previousElementSibling.click()">
                Click to upload files or drag and drop
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4">
            <button type="button" 
                    @click="showNewTicketForm = false"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
              Cancel
            </button>
            <button type="submit"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
              Create Ticket
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
