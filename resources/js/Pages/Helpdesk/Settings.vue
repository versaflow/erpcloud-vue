<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { useSidebar } from '@/Composables/useSidebar.js';

const { isCollapsed } = useSidebar();
const activeTab = ref('email');

// Email configurations
const imapConfigs = ref([
  { id: 1, email: '', host: '', port: '', username: '', password: '', enabled: true }
]);

const smtpConfig = ref({
  fromName: '',
  email: '',
  host: '',
  port: '',
  username: '',
  password: '',
  encryption: 'tls',
  emailTemplates: {
    newConversation: '',
    conversationReply: ''
  },
  settings: {
    sendConfirmation: true,
    staffNotifications: true,
    includeHistory: true
  },
  signatures: {
    default: '',
    personal: [] // Array of user signatures
  }
});

const departments = ref([
  { id: 1, name: '', email: '', description: '' }
]);

const addImapConfig = () => {
  if (imapConfigs.value.length < 5) {
    imapConfigs.value.push({
      id: Date.now(),
      email: '', host: '', port: '', username: '', password: '', enabled: true
    });
  }
};

const addDepartment = () => {
  departments.value.push({
    id: Date.now(), name: '', email: '', description: ''
  });
};

const removeItem = (array, index) => array.splice(index, 1);

// Add new signature management
const signatures = ref([
  { id: 1, name: 'Default', content: '', isDefault: true },
  { id: 2, name: 'Personal', content: '', isDefault: false }
]);

const addSignature = () => {
  signatures.value.push({
    id: Date.now(),
    name: `Signature ${signatures.value.length + 1}`,
    content: '',
    isDefault: false
  });
};

const removeSignature = (index) => {
  if (!signatures.value[index].isDefault) {
    signatures.value.splice(index, 1);
  }
};

const setDefaultSignature = (index) => {
  signatures.value.forEach(sig => sig.isDefault = false);
  signatures.value[index].isDefault = true;
};
</script>

<template>
  <AuthenticatedLayout>
    <header class="bg-white shadow">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="py-6 text-xl font-semibold text-gray-800">Helpdesk Settings</h2>
      </div>
    </header>

    <main class="p-6">
      <div class="mx-auto max-w-7xl">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in ['email', 'departments', 'general']"
              :key="tab"
              @click="activeTab = tab"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                activeTab === tab
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab.charAt(0).toUpperCase() + tab.slice(1) }} Configuration
            </button>
          </nav>
        </div>

        <!-- Email Configuration Tab -->
        <div v-show="activeTab === 'email'" class="space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium">IMAP Email Accounts</h3>
              <button
                @click="addImapConfig"
                :disabled="imapConfigs.length >= 5"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
              >
                Add IMAP Account
              </button>
            </div>

            <div class="space-y-4">
              <div v-for="(config, index) in imapConfigs" :key="config.id"
                class="border rounded-lg p-4 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <input v-model="config.email" placeholder="Email Address" 
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <input v-model="config.host" placeholder="IMAP Host"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <input v-model="config.port" placeholder="Port" type="number"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <input v-model="config.username" placeholder="Username"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <input v-model="config.password" placeholder="Password" type="password"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  
                  <div class="flex items-center justify-between">
                    <label class="flex items-center">
                      <input type="checkbox" v-model="config.enabled" class="rounded border-gray-300 text-indigo-600">
                      <span class="ml-2 text-sm text-gray-600">Enable Account</span>
                    </label>
                    <button @click="removeItem(imapConfigs, index)"
                      class="text-red-600 hover:text-red-700">
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SMTP Configuration -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">SMTP Outgoing Email</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">From Name</label>
                  <input v-model="smtpConfig.fromName" placeholder="Support Team"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Email Address</label>
                  <input v-model="smtpConfig.email" placeholder="support@example.com"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
                  <input v-model="smtpConfig.host" placeholder="smtp.example.com"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
              </div>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">SMTP Port</label>
                  <input v-model="smtpConfig.port" type="number" placeholder="587"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Encryption</label>
                  <select v-model="smtpConfig.encryption"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="none">None</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Username</label>
                  <input v-model="smtpConfig.username"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Password</label>
                  <input v-model="smtpConfig.password" type="password"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
              </div>
            </div>

            <!-- Signatures Section -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-medium">Email Signatures</h4>
                <button 
                  @click="addSignature"
                  class="bg-indigo-600 text-white px-3 py-2 text-sm rounded-lg hover:bg-indigo-700"
                >
                  Add Signature
                </button>
              </div>

              <div class="space-y-6">
                <div v-for="(signature, index) in signatures" 
                    :key="signature.id"
                    class="border rounded-lg p-4 bg-gray-50"
                >
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-4">
                      <input 
                        v-model="signature.name"
                        placeholder="Signature Name"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      >
                      <label class="flex items-center">
                        <input 
                          type="radio"
                          :checked="signature.isDefault"
                          @change="setDefaultSignature(index)"
                          class="rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="ml-2 text-sm text-gray-600">Set as Default</span>
                      </label>
                    </div>
                    <button 
                      v-if="!signature.isDefault"
                      @click="removeSignature(index)"
                      class="text-red-600 hover:text-red-700"
                    >
                      Remove
                    </button>
                  </div>

                  <div class="mt-3">
                    <textarea 
                      v-model="signature.content"
                      rows="4"
                      placeholder="Enter your signature here... HTML supported"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                  </div>

                  <!-- Signature Preview -->
                  <div v-if="signature.content" class="mt-3 p-3 border rounded bg-white">
                    <div class="text-sm text-gray-500 mb-2">Preview:</div>
                    <div v-html="signature.content" class="prose prose-sm max-w-none"></div>
                  </div>

                  <!-- Signature Variables Help -->
                  <div class="mt-3 text-sm text-gray-500">
                    Available variables:
                    <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">
                      {name}, {email}, {department}, {position}
                    </code>
                  </div>
                </div>
              </div>
            </div>

            <!-- Email Settings -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <h4 class="text-lg font-medium mb-4">Email Settings</h4>
              <div class="space-y-4">
                <label class="flex items-center">
                  <input type="checkbox" class="rounded border-gray-300 text-indigo-600">
                  <span class="ml-2">Send confirmation emails</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" class="rounded border-gray-300 text-indigo-600">
                  <span class="ml-2">Enable email notifications for staff</span>
                </label>
                <label class="flex items-center">
                  <input type="checkbox" class="rounded border-gray-300 text-indigo-600">
                  <span class="ml-2">Include ticket history in emails</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Departments Tab -->
        <div v-show="activeTab === 'departments'" class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Departments</h3>
            <button @click="addDepartment"
              class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
              Add Department
            </button>
          </div>

          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(dept, index) in departments" :key="dept.id">
                <td class="px-6 py-4">
                  <input v-model="dept.name" placeholder="Department Name"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </td>
                <td class="px-6 py-4">
                  <input v-model="dept.email" placeholder="Email"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </td>
                <td class="px-6 py-4">
                  <input v-model="dept.description" placeholder="Description"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </td>
                <td class="px-6 py-4">
                  <button @click="removeItem(departments, index)"
                    class="text-red-600 hover:text-red-700">
                    Remove
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- General Settings Tab -->
        <div v-show="activeTab === 'general'" class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium mb-4">General Settings</h3>
          <div class="space-y-4">
            <label class="flex items-center">
              <input type="checkbox" class="rounded border-gray-300 text-indigo-600">
              <span class="ml-2">Auto-assign conversations</span>
            </label>
            // ... other general settings ...
          </div>
        </div>
      </div>
    </main>
  </AuthenticatedLayout>
</template>

<style>
/* Add support for HTML content in signature preview */
.prose img {
  max-width: 100%;
  height: auto;
}

.prose a {
  color: #4f46e5;
  text-decoration: underline;
}
</style>