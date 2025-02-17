<template>
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
      <div v-for="(config, index) in imapConfigs" :key="config.id" class="border rounded-lg p-4 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input v-model="config.email" placeholder="Email Address" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <input v-model="config.host" placeholder="IMAP Host" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <input v-model="config.port" placeholder="Port" type="number" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <input v-model="config.username" placeholder="Username" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <input v-model="config.password" placeholder="Password" type="password" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          
          <!-- Add Department Assignment -->
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Assign to Department (Optional)</label>
            <select v-model="config.department_id" @change="updateImapConfig(config)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
              <option :value="null">No Department</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
            </select>
          </div>

          <!-- Advanced IMAP Settings -->
          <div class="col-span-2 space-y-3">
            <h4 class="font-medium text-sm text-gray-700">Advanced Settings</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-600">Encryption</label>
                <select v-model="config.imap_settings.encryption" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <option value="ssl">SSL</option>
                  <option value="tls">TLS</option>
                  <option value="none">None</option>
                </select>
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <input type="checkbox" v-model="config.imap_settings.validate_cert" class="rounded border-gray-300 text-indigo-600">
                  <span class="ml-2 text-sm text-gray-600">Validate Certificate</span>
                </label>
              </div>
            </div>
          </div>

          <!-- New Control Section at Bottom -->
          <div class="col-span-2 flex items-center justify-between border-t pt-4 mt-4">
            <!-- Remove Button on Left -->
            <button @click="removeItem(imapConfigs, index)" class="text-red-600 hover:text-red-700">Remove</button>

            <!-- Controls on Right -->
            <div class="flex items-center gap-4">
              <!-- Last Sync Info -->
              <span v-if="config.last_sync_at" class="text-sm text-gray-500">Last sync: {{ config.last_sync_at }}</span>

              <!-- Enable Switch -->
              <label class="flex items-center">
                <input type="checkbox" v-model="config.enabled" class="rounded border-gray-300 text-indigo-600">
                <span class="ml-2 text-sm text-gray-600">Enable</span>
              </label>

              <!-- Sync Button -->
              <button @click="syncEmails(config)" :disabled="!config.enabled || isSyncing" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                <svg v-if="isSyncing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ isSyncing ? 'Syncing...' : 'Sync Now' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  imapConfigs: Array,
  departments: Array,
  isSyncing: Boolean
});

const emit = defineEmits(['addImapConfig', 'removeItem', 'updateImapConfig', 'syncEmails']);

const addImapConfig = () => emit('addImapConfig');
const removeItem = (array, index) => emit('removeItem', array, index);
const updateImapConfig = (config) => emit('updateImapConfig', config);
const syncEmails = (config) => emit('syncEmails', config);
</script>
