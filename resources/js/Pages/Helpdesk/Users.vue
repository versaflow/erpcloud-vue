<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';

// Sample user data
const users = ref([
  {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    lastEmail: '2024-02-12 10:30 AM',
    lastContact: '2024-02-12',
    totalConversations: 3,  // Changed from totalTickets
    status: 'active'
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane@example.com',
    lastEmail: '2024-02-11 03:15 PM',
    lastContact: '2024-02-11',
    totalConversations: 5,  // Changed from totalTickets
    status: 'active'
  },
  {
    id: 3,
    name: 'Bob Wilson',
    email: 'bob@example.com',
    lastEmail: '2024-02-10 09:45 AM',
    lastContact: '2024-02-10',
    totalConversations: 2,  // Changed from totalTickets
    status: 'inactive'
  }
]);

const search = ref('');
const sortBy = ref('lastContact');
const sortDesc = ref(true);

// Add sorting logic to filteredUsers
const filteredUsers = computed(() => {
  let filtered = users.value.filter(user => 
    user.name.toLowerCase().includes(search.value.toLowerCase()) ||
    user.email.toLowerCase().includes(search.value.toLowerCase())
  );
  
  // Sort users
  return filtered.sort((a, b) => {
    let comparison = 0;
    switch (sortBy.value) {
      case 'name':
        comparison = a.name.localeCompare(b.name);
        break;
      case 'totalConversations':  // Changed from totalTickets
        comparison = a.totalConversations - b.totalConversations;
        break;
      case 'lastContact':
        comparison = new Date(b.lastContact) - new Date(a.lastContact);
        break;
    }
    return sortDesc.value ? comparison : -comparison;
  });
});

// Add sort handler
const handleSort = (field) => {
  if (sortBy.value === field) {
    sortDesc.value = !sortDesc.value;
  } else {
    sortBy.value = field;
    sortDesc.value = true;
  }
};
</script>

<template>
  <AuthenticatedLayout>
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800">Helpdesk Users</h2>
      </div>
    </header>

    <main class="p-6">
      <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <!-- Search and Filters -->
          <div class="flex gap-4 mb-6">
            <div class="flex-1">
              <input
                v-model="search"
                type="text"
                placeholder="Search users..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
            </div>
            <select
              v-model="sortBy"
              class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="lastContact">Last Contact</option>
              <option value="name">Name</option>
              <option value="totalConversations">Total Conversations</option>
            </select>
          </div>

          <!-- Users Table -->
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th @click="handleSort('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Email</th>
                <th @click="handleSort('lastContact')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Last Contact</th>
                <th @click="handleSort('totalConversations')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Conversations</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in filteredUsers" :key="user.id">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div>
                      <div class="font-medium text-gray-900">{{ user.name }}</div>
                      <div class="text-gray-500">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ user.lastEmail }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ user.lastContact }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ user.totalConversations }}</td>
                <td class="px-6 py-4">
                  <span :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ user.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  <button class="text-indigo-600 hover:text-indigo-900">View History</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </AuthenticatedLayout>
</template>

<style>
.cursor-pointer {
  cursor: pointer;
}

th:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>
