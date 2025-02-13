<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Add props for user role
const props = defineProps({
    isAdmin: {
        type: Boolean,
        required: true
    }
});

const users = ref([]);
const isLoading = ref(true);
const search = ref('');
const sortBy = ref('lastContact');
const sortDesc = ref(true);

// Add delete confirmation
const showDeleteModal = ref(false);
const userToDelete = ref(null);

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const deleteUser = async () => {
    if (!props.isAdmin) {
        alert('Only administrators can delete users');
        return;
    }

    try {
        await axios.delete(route('helpdesk.users.delete', userToDelete.value.id));
        showSuccess('User deleted successfully');
        await fetchUsers();
        showDeleteModal.value = false;
        userToDelete.value = null;
    } catch (error) {
        if (error.response?.status === 403) {
            alert('Unauthorized. Only admins can delete users.');
        } else {
            console.error('Failed to delete user:', error);
            alert('Failed to delete user');
        }
    }
};

const showSuccess = (message) => {
    // Implement your preferred notification system
    alert(message);
};

// Add actions
const newTicket = (user) => {
    router.get(route('helpdesk.tickets.create'), { user_id: user.id });
};

const editUser = (user) => {
    // Change from router.get to window.location.href for a full page load
    window.location.href = route('helpdesk.users.edit', user.id);
};

// Replace static data with reactive data from API
const fetchUsers = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(route('helpdesk.users.list'));
        users.value = response.data;
    } catch (error) {
        console.error('Failed to fetch users:', error);
    } finally {
        isLoading.value = false;
    }
};

// Add computed property for filtered users
const filteredUsers = computed(() => {
    let filtered = users.value.filter(user => 
        user.name.toLowerCase().includes(search.value.toLowerCase()) ||
        user.email.toLowerCase().includes(search.value.toLowerCase())
    );
    
    return filtered.sort((a, b) => {
        let comparison = 0;
        switch (sortBy.value) {
            case 'name':
                comparison = a.name.localeCompare(b.name);
                break;
            case 'totalConversations':
                comparison = a.totalConversations - b.totalConversations;
                break;
            case 'lastContact':
                comparison = new Date(b.lastContact) - new Date(a.lastContact);
                break;
        }
        return sortDesc.value ? comparison : -comparison;
    });
});

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = field;
        sortDesc.value = true;
    }
};

// Fetch users on component mount
onMounted(() => {
    fetchUsers();
});
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
          <!-- Loading State -->
          <div v-if="isLoading" class="flex justify-center items-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          </div>

          <!-- Content -->
          <template v-else>
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                  <th @click="handleSort('lastContact')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Last Contact</th>
                  <th @click="handleSort('totalConversations')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Conversations</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <div>
                        <div class="font-medium text-gray-900">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-1">
                      <span v-for="tag in user.tags" :key="tag"
                        class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">
                        {{ tag }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">
                    {{ user.lastContact || 'Never' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">
                    {{ user.totalConversations }}
                  </td>
                  <td class="px-6 py-4">
                    <span :class="[
                      'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                      user.status === 'active' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-gray-100 text-gray-800'
                    ]">
                      {{ user.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="flex items-center space-x-3">
                      <button 
                        @click="newTicket(user)"
                        class="text-indigo-600 hover:text-indigo-900"
                        title="New Ticket"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                      </button>
                      <button 
                        @click="editUser(user)"
                        class="text-gray-600 hover:text-gray-900"
                        title="Edit User"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <!-- Only show delete button for admins -->
                      <button 
                        v-if="isAdmin"
                        @click="confirmDelete(user)"
                        class="text-red-600 hover:text-red-900"
                        title="Delete User"
                      >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- No Results Message -->
            <div v-if="filteredUsers.length === 0" class="text-center py-8 text-gray-500">
              No users found matching your search.
            </div>
          </template>
        </div>
      </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg p-6 max-w-sm w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Delete User</h3>
        <p class="text-sm text-gray-500 mb-4">
          Are you sure you want to delete {{ userToDelete?.name }}? This action cannot be undone.
        </p>
        <div class="flex justify-end space-x-3">
          <button 
            @click="showDeleteModal = false"
            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 border rounded-md"
          >
            Cancel
          </button>
          <button 
            @click="deleteUser"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
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
