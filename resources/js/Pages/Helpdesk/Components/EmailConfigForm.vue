<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-medium">Email Account Configuration</h3>
      <button
        @click="$emit('addConfig')"
        :disabled="configs.length >= 5"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
      >
        Add Email Configuration
      </button>
    </div>

    <div class="space-y-8">
      <div v-for="(config, index) in configs" :key="config.id" class="border rounded-lg p-6 bg-gray-50">
        <!-- Config Header -->
        <div class="flex justify-between items-center mb-6">
          <h4 class="font-medium">Email Configuration #{{ index + 1 }}</h4>
          <button @click="$emit('removeConfig', index)" class="text-red-600 hover:text-red-700">
            Remove
          </button>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-4">
            <button
              v-for="tab in ['IMAP', 'SMTP', 'Signature']"
              :key="tab"
              @click="activeTab[index] = tab.toLowerCase()"
              :class="[
                'py-2 px-3 text-sm font-medium border-b-2',
                activeTab[index] === tab.toLowerCase()
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab }} Settings
            </button>
          </nav>
        </div>

        <!-- IMAP Settings -->
        <div v-show="activeTab[index] === 'imap'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Email Address</label>
              <input v-model="config.email" placeholder="Email Address" 
                :class="[
                  'mt-1 w-full rounded-md shadow-sm',
                  getError(index, 'email') ? 
                  'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                  'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                ]">
              <p v-if="getError(index, 'email')" class="mt-1 text-sm text-red-600">
                {{ getError(index, 'email') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">IMAP Host</label>
              <input v-model="config.host" placeholder="imap.example.com" 
                :class="[
                  'mt-1 w-full rounded-md shadow-sm',
                  getError(index, 'host') ? 
                  'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                  'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                ]">
              <p v-if="getError(index, 'host')" class="mt-1 text-sm text-red-600">
                {{ getError(index, 'host') }}
              </p>
            </div>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Port</label>
                <input v-model="config.port" type="number" placeholder="993" 
                  :class="[
                    'mt-1 w-full rounded-md shadow-sm',
                    getError(index, 'port') ? 
                    'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                  ]">
                <p v-if="getError(index, 'port')" class="mt-1 text-sm text-red-600">
                  {{ getError(index, 'port') }}
                </p>
              </div>
              
              <div>
                <label class="block text-sm text-gray-600">Encryption</label>
                <select v-model="config.imap_settings.encryption" 
                  class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <option value="ssl">SSL</option>
                  <option value="tls">TLS</option>
                  <option value="none">None</option>
                </select>
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input v-model="config.username" placeholder="Username" 
                  :class="[
                    'mt-1 w-full rounded-md shadow-sm',
                    getError(index, 'username') ? 
                    'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                  ]">
                <p v-if="getError(index, 'username')" class="mt-1 text-sm text-red-600">
                  {{ getError(index, 'username') }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input v-model="config.password" type="password" placeholder="Password" 
                  :class="[
                    'mt-1 w-full rounded-md shadow-sm',
                    getError(index, 'password') ? 
                    'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                  ]">
                <p v-if="getError(index, 'password')" class="mt-1 text-sm text-red-600">
                  {{ getError(index, 'password') }}
                </p>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Department</label>
              <select v-model="config.department_id" 
                class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option :value="null">No Department</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Advanced IMAP Settings -->
          <div class="mt-4 space-y-4">
            <h5 class="font-medium text-sm text-gray-700">Advanced Settings</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-center">
                <label class="flex items-center">
                  <input type="checkbox" v-model="config.imap_settings.validate_cert" 
                    class="rounded border-gray-300 text-indigo-600">
                  <span class="ml-2 text-sm text-gray-600">Validate Certificate</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- SMTP Settings -->
        <div v-show="activeTab[index] === 'smtp'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">From Name</label>
              <input 
                v-model="config.smtp_config.from_name" 
                placeholder="Support Team"
                @change="watchImapEmail(config)"
                :class="[
                  'mt-1 w-full rounded-md shadow-sm',
                  getSmtpError(index, 'from_name') ? 
                  'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                  'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                ]"
              >
              <p v-if="getSmtpError(index, 'from_name')" class="mt-1 text-sm text-red-600">
                {{ getSmtpError(index, 'from_name') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Email Address</label>
              <input 
                v-model="config.smtp_config.email" 
                placeholder="support@example.com"
                @change="watchImapEmail(config)"
                :class="[
                  'mt-1 w-full rounded-md shadow-sm',
                  getSmtpError(index, 'email') ? 
                  'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                  'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                ]"
              >
              <p v-if="getSmtpError(index, 'email')" class="mt-1 text-sm text-red-600">
                {{ getSmtpError(index, 'email') }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
              <input 
                v-model="config.smtp_config.host" 
                placeholder="smtp.example.com"
                :class="[
                  'mt-1 w-full rounded-md shadow-sm',
                  getSmtpError(index, 'host') ? 
                  'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                  'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                ]"
              >
              <p v-if="getSmtpError(index, 'host')" class="mt-1 text-sm text-red-600">
                {{ getSmtpError(index, 'host') }}
              </p>
            </div>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Port</label>
                <input 
                  v-model="config.smtp_config.port" 
                  type="number" 
                  placeholder="587"
                  :class="[
                    'mt-1 w-full rounded-md shadow-sm',
                    getSmtpError(index, 'port') ? 
                    'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                  ]"
                >
                <p v-if="getSmtpError(index, 'port')" class="mt-1 text-sm text-red-600">
                  {{ getSmtpError(index, 'port') }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Encryption</label>
                <select 
                  v-model="config.smtp_config.encryption"
                  class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                  <option value="none">None</option>
                </select>
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input 
                  v-model="config.smtp_config.username"
                  :class="[
                    'mt-1 w-full rounded-md shadow-sm',
                    getSmtpError(index, 'username') ? 
                    'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                  ]"
                >
                <p v-if="getSmtpError(index, 'username')" class="mt-1 text-sm text-red-600">
                  {{ getSmtpError(index, 'username') }}
                </p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input 
                  v-model="config.smtp_config.password" 
                  type="password"
                  :class="[
                    'mt-1 w-full rounded-md shadow-sm',
                    getSmtpError(index, 'password') ? 
                    'border-red-500 focus:border-red-500 focus:ring-red-500' : 
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                  ]"
                >
                <p v-if="getSmtpError(index, 'password')" class="mt-1 text-sm text-red-600">
                  {{ getSmtpError(index, 'password') }}
                </p>
              </div>
            </div>
          </div>

       
        </div>

        <!-- Signature Settings -->
        <div v-show="activeTab[index] === 'signature'" class="space-y-4">
          <div v-for="(sig, sigIndex) in config.signatures" :key="sigIndex" class="border rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-4">
                <input v-model="sig.name" placeholder="Signature Name"
                  class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <label class="flex items-center">
                  <input type="radio" :checked="sig.isDefault" @change="setDefaultSignature(config, sigIndex)"
                    class="rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-500">
                  <span class="ml-2 text-sm text-gray-600">Set as Default</span>
                </label>
              </div>
              <button v-if="!sig.isDefault" @click="removeSignature(config, sigIndex)"
                class="text-red-600 hover:text-red-700">
                Remove
              </button>
            </div>

            <textarea v-model="sig.content" rows="4" placeholder="Enter your signature here... HTML supported"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </textarea>

            <div v-if="sig.content" class="mt-3 p-3 border rounded bg-white">
              <div class="text-sm text-gray-500 mb-2">Preview:</div>
              <div v-html="sig.content" class="prose prose-sm max-w-none"></div>
            </div>
          </div>

          <button @click="addSignature(config)"
            class="mt-4 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            Add Signature
          </button>
        </div>

        <!-- Control Section -->
        <div class="border-t mt-6 pt-4 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <label class="flex items-center">
              <input type="checkbox" v-model="config.enabled" class="rounded border-gray-300 text-indigo-600">
              <span class="ml-2 text-sm text-gray-600">Enable</span>
            </label>
            <span v-if="config.last_sync_at" class="text-sm text-gray-500">
              Last sync: {{ config.last_sync_at }}
            </span>
          </div>

          <button v-if="config.id"
            @click="$emit('syncEmails', config)"
            :disabled="!config.enabled || isConfigSyncing(config.id)"
            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            <svg v-if="isConfigSyncing(config.id)" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isConfigSyncing(config.id) ? 'Syncing...' : 'Sync Now' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  configs: {
    type: Array,
    required: true
  },
  departments: {
    type: Array,
    required: true
  },
  syncingStates: {
    type: Map,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const activeTab = ref({});

const addSignature = (config) => {
  if (!config.signatures) {
    config.signatures = [];
  }
  config.signatures.push({
    name: `Signature ${config.signatures.length + 1}`,
    content: 'Best regards,\n{name}',
  });
};

const removeSignature = (config, index) => {
  if (!config.signatures[index].isDefault) {
    config.signatures.splice(index, 1);
  }
};

const setDefaultSignature = (config, index) => {
  config.signatures.forEach(sig => sig.isDefault = false);
  config.signatures[index].isDefault = true;
};

const getError = (configIndex, field) => {
  return props.errors[`imap_configs.${configIndex}.${field}`]?.[0];
};

const getSmtpError = (configIndex, field) => {
  return props.errors[`imap_configs.${configIndex}.smtp_config.${field}`]?.[0];
};

// Add watcher for IMAP email changes
const watchImapEmail = (config) => {
  if (!config.smtp_config) {
    config.smtp_config = {
      from_name: '',
      email: '',
      host: '',
      port: '',
      username: '',
      password: '',
      encryption: 'tls'
    };
  }
  
  if (config.email && (!config.smtp_config.email || !config.smtp_config.from_name)) {
    config.smtp_config = {
      ...config.smtp_config,
      email: config.email.trim(),
      from_name: config.email.trim(),
      username: config.username.trim(),
      host: config.host,
      password: config.password
    };
  }
};

const isConfigSyncing = (configId) => props.syncingStates.get(configId) || false;

defineEmits(['addConfig', 'removeConfig', 'syncEmails', 'update:configs']);
</script>
