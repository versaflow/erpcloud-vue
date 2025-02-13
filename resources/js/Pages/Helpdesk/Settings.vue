<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, onMounted } from 'vue';
import { useSidebar } from '@/Composables/useSidebar.js';

// Update the props definition
const props = defineProps({
    departments: {
        type: Array,
        required: true,
        default: () => []
    },
    emailSettings: {
        type: Object,
        required: true, // Change to true
        default: () => ({
            imap_accounts: [],
            smtp_config: {
                fromName: '',
                email: '',
                host: '',
                port: '',
                username: '',
                password: '',
                encryption: 'tls'
            },
            signatures: []
        })
    }
});

const { isCollapsed } = useSidebar();
const activeTab = ref('email');

// Track form changes
const hasChanges = ref(false);
const isSaving = ref(false);
const isSyncing = ref(false);

// Update the component initialization
const imapConfigs = ref(props.emailSettings?.imap_accounts?.length > 0
    ? props.emailSettings.imap_accounts
    : [{
        id: null,  // Remove Date.now(), let database assign ID
        email: '', 
        host: '', 
        port: '', 
        username: '', 
        password: '', 
        enabled: true,
        department_id: null,
        imap_settings: {
            encryption: 'ssl',
            validate_cert: true
        }
    }]
);

// Initialize SMTP config with proper field name
const smtpConfig = ref(props.emailSettings?.smtp_config ? {
    fromName: props.emailSettings.smtp_config.from_name,  // Map from DB field to frontend
    email: props.emailSettings.smtp_config.email,
    host: props.emailSettings.smtp_config.host,
    port: props.emailSettings.smtp_config.port,
    username: props.emailSettings.smtp_config.username,
    password: props.emailSettings.smtp_config.password,
    encryption: props.emailSettings.smtp_config.encryption || 'tls'
} : {
    fromName: '',  // Use frontend field name
    email: '',
    host: '',
    port: '',
    username: '',
    password: '',
    encryption: 'tls'
});

// Initialize signatures with provided values or default
const signatures = ref(props.emailSettings?.signatures?.length > 0 
    ? props.emailSettings.signatures 
    : [{
        id: 1,
        name: 'Default',
        content: 'Best regards,\n{name}\n{position}\n{department}',
        isDefault: true
    }]
);

const addImapConfig = () => {
  if (imapConfigs.value.length < 5) {
    imapConfigs.value.push({
      id: Date.now(),
      email: '', host: '', port: '', username: '', password: '', enabled: true,
      department_id: null,
      imap_settings: {
        encryption: 'ssl',
        validate_cert: true
      }
    });
  }
};

const addDepartment = () => {
  departments.value.push({
    id: Date.now(), name: '', email: '', description: ''
  });
};

const removeItem = (array, index) => array.splice(index, 1);

// Update addSignature to start counting from 2 since we only have one default
const addSignature = () => {
  signatures.value.push({
    id: Date.now(),
    name: `Signature ${signatures.value.length + 1}`,
    content: 'Regards,\n{name}',
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
  signatures.value[index].isDefault = true;x
};

// Add department selection to IMAP config form
const updateImapConfig = (config) => {
    hasChanges.value = true;
};

const syncEmails = async (imapConfig) => {
    // Don't try to sync if no ID (unsaved config)
    if (!imapConfig.id) {
        alert('Please save the email settings first before syncing.');
        return;
    }

    isSyncing.value = true;
    try {
        const response = await axios.post(route('helpdesk.email.sync'), {
            email_id: imapConfig.id
        });
        
        // Update last sync time
        imapConfig.last_sync_at = response.data.last_sync;
        showSuccess('Email sync completed successfully');
    } catch (error) {
        console.error('Sync failed:', error);
        alert('Failed to sync emails: ' + (error.response?.data?.error || error.message));
    } finally {
        isSyncing.value = false;
    }
};

// Add error handling
const errors = ref({});
const showError = ref(false);

// Save all settings
const saveSettings = async () => {
    errors.value = {};
    showError.value = false;
    isSaving.value = true;
    
    try {
        const { data } = await axios.post(route('helpdesk.settings.save'), {
            imap_configs: imapConfigs.value,
            smtp_config: {
                from_name: smtpConfig.value.from_name,
                email: smtpConfig.value.email,
                host: smtpConfig.value.host,
                port: smtpConfig.value.port,
                username: smtpConfig.value.username,
                password: smtpConfig.value.password,
                encryption: smtpConfig.value.encryption
            },
            signatures: signatures.value.map(sig => ({
                name: sig.name,
                content: sig.content,
                isDefault: sig.isDefault ?? false  // Ensure isDefault is always defined
            }))
        });

        // Update the IMAP configs with saved data
        if (data.imap_accounts) {
            imapConfigs.value = data.imap_accounts;
        }
        
        hasChanges.value = false;
        showSuccess(data.message || 'Settings saved successfully');
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
            showError.value = true;
        }
        console.error('Error saving settings:', error);
    } finally {
        isSaving.value = false;
    }
};

// Add helper function for showing alerts
const showSuccess = (message) => {
    // You can implement your preferred notification system here
    alert(message); // Replace with your notification system
};

// Watch for changes
watch([imapConfigs, smtpConfig, signatures], () => {
    hasChanges.value = true;
}, { deep: true });

// Add monitoring to your Settings.vue component
const queueStatus = ref({
    jobs: 0,
    failed_jobs: 0,
    workers_running: false,
    last_job_processed: null
});

const fetchQueueStatus = async () => {
    try {
        const response = await axios.get(route('helpdesk.queue.status'));
        queueStatus.value = response.data;
    } catch (error) {
        console.error('Failed to fetch queue status:', error);
    }
};

// Add console logging
const cronStatus = ref({
    last_run: null
});

const fetchCronStatus = async () => {
    try {
        const response = await axios.get(route('helpdesk.cron.status'));
        cronStatus.value = response.data;
    } catch (error) {
        console.error('Failed to fetch cron status:', error);
    }
};

onMounted(() => {
    fetchCronStatus();
    // Update status every minute
    setInterval(fetchCronStatus, 60000);
});

// Add WhatsApp configuration state
const whatsappConfig = ref({
    enabled: false,
    phone_number: '',
    api_key: '',
    api_secret: '',
    webhook_url: '',
    business_account_id: '',
    template_namespace: '',
    template_language: 'en',
    auto_reply: false,
    auto_reply_message: 'Thank you for contacting us. We will respond shortly.',
    last_webhook_received: null  // Add this line
});

// Update tab list
const tabs = ['email', 'departments', 'social', 'spam', 'general'];

// Add spam management state
const spamContacts = ref([]);
const isLoadingSpam = ref(false);
const newSpamContact = ref({
    type: 'email',
    value: '',
    reason: ''
});

const fetchSpamContacts = async () => {
    try {
        isLoadingSpam.value = true;
        const response = await axios.get(route('helpdesk.spam.list'));
        spamContacts.value = response.data;
    } catch (error) {
        console.error('Failed to fetch spam contacts:', error);
    } finally {
        isLoadingSpam.value = false;
    }
};

const addSpamContact = async () => {
    try {
        await axios.post(route('helpdesk.spam.add'), newSpamContact.value);
        await fetchSpamContacts();
        newSpamContact.value = { type: 'email', value: '', reason: '' };
    } catch (error) {
        console.error('Failed to add spam contact:', error);
    }
};

const removeFromSpam = async (id) => {
    try {
        await axios.delete(route('helpdesk.spam.delete', id));
        await fetchSpamContacts();
    } catch (error) {
        console.error('Failed to remove spam contact:', error);
    }
};

onMounted(() => {
    fetchCronStatus();
    // Update status every minute
    setInterval(fetchCronStatus, 60000);
    if (activeTab.value === 'spam') {
        fetchSpamContacts();
    }
});

watch(activeTab, (newTab) => {
    if (newTab === 'spam') {
        fetchSpamContacts();
    }
});
</script>

<template>
  <AuthenticatedLayout>
    <!-- Debug data display -->
    <!-- <div v-if="true" class="p-4 bg-gray-100 text-xs">
      <pre>{{ emailSettings }}</pre>
    </div> -->
    <!-- Add Error Alert -->
    <!-- <div v-if="showError" class="bg-red-50 p-4 mb-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 101.414 1.414L10 11.414l1.293-1.293a1 1 001.414-1.414L11.414 10l1.293-1.293a1 1 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
          <div class="mt-2 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
              <li v-for="(errorArray, field) in errors" :key="field">
                {{ errorArray[0] }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div> -->

    <!-- Header with Save Button -->
    <header class="bg-white shadow">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <h2 class="text-xl font-semibold text-gray-800">Helpdesk Settings</h2>
          <button
            @click="saveSettings"
            :disabled="!hasChanges || isSaving"
            :class="[
              'px-4 py-2 rounded-lg text-white',
              hasChanges 
                ? 'bg-indigo-600 hover:bg-indigo-700' 
                : 'bg-gray-400 cursor-not-allowed'
            ]"
          >
            <span v-if="isSaving">Saving...</span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </div>
    </header>

    <main class="p-6">
      <div class="mx-auto max-w-7xl">
        <!-- Update Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in tabs"
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
          <!-- Add email sync status at the top of email tab -->
          <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-gray-700">Email Sync Status</h3>
              <span class="text-sm text-gray-500">
                Last Run: {{ cronStatus.last_run || 'Never' }}
              </span>
            </div>
          </div>
          
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
                  
                  <!-- Add Department Assignment -->
                  <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Assign to Department (Optional)
                    </label>
                    <select
                      v-model="config.department_id"
                      @change="updateImapConfig(config)"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option :value="null">No Department</option>
                      <option v-for="dept in departments" 
                              :key="dept.id" 
                              :value="dept.id">
                        {{ dept.name }}
                      </option>
                    </select>
                  </div>

                  <!-- Advanced IMAP Settings -->
                  <div class="col-span-2 space-y-3">
                    <h4 class="font-medium text-sm text-gray-700">Advanced Settings</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm text-gray-600">Encryption</label>
                        <select
                          v-model="config.imap_settings.encryption"
                          class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                          <option value="ssl">SSL</option>
                          <option value="tls">TLS</option>
                          <option value="none">None</option>
                        </select>
                      </div>
                      <div class="flex items-center">
                        <label class="flex items-center">
                          <input
                            type="checkbox"
                            v-model="config.imap_settings.validate_cert"
                            class="rounded border-gray-300 text-indigo-600"
                          >
                          <span class="ml-2 text-sm text-gray-600">
                            Validate Certificate
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- New Control Section at Bottom -->
                  <div class="col-span-2 flex items-center justify-between border-t pt-4 mt-4">
                    <!-- Remove Button on Left -->
                    <button @click="removeItem(imapConfigs, index)"
                            class="text-red-600 hover:text-red-700">
                      Remove
                    </button>

                    <!-- Controls on Right -->
                    <div class="flex items-center gap-4">
                      <!-- Last Sync Info -->
                      <span v-if="config.last_sync_at" class="text-sm text-gray-500">
                        Last sync: {{ config.last_sync_at }}
                      </span>

                      <!-- Enable Switch -->
                      <label class="flex items-center">
                        <input type="checkbox" v-model="config.enabled" 
                              class="rounded border-gray-300 text-indigo-600">
                        <span class="ml-2 text-sm text-gray-600">Enable</span>
                      </label>

                      <!-- Sync Button -->
                      <button @click="syncEmails(config)"
                              :disabled="!config.enabled || isSyncing"
                              class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg
                                    text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                        <svg v-if="isSyncing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" 
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" 
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                          </path>
                        </svg>
                        <span>{{ isSyncing ? 'Syncing...' : 'Sync Now' }}</span>
                      </button>
                    </div>
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
                    :class="{'border-red-500': errors['smtp_config.fromName']}"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <p v-if="errors['smtp_config.fromName']" class="mt-1 text-sm text-red-600">
                    {{ errors['smtp_config.fromName'][0] }}
                  </p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Email Address</label>
                  <input v-model="smtpConfig.email" placeholder="support@example.com"
                    :class="{'border-red-500': errors['smtp_config.email']}"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <p v-if="errors['smtp_config.email']" class="mt-1 text-sm text-red-600">
                    {{ errors['smtp_config.email'][0] }}
                  </p>
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

            <!-- Remove the Email Settings section completely -->
          </div>
        </div>

        <!-- Add Social Integration Tab -->
        <div v-show="activeTab === 'social'" class="space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-lg font-medium">WhatsApp Business API</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Last webhook received: {{ whatsappConfig.last_webhook_received || 'No webhooks yet' }}
                </p>
              </div>
              <div class="flex items-center">
                <span class="mr-3 text-sm text-gray-500">Enable WhatsApp</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="whatsappConfig.enabled" class="sr-only peer">
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
              </div>
            </div>

            <div class="space-y-6" :class="{ 'opacity-50': !whatsappConfig.enabled }">
              <!-- Basic Settings -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Business Phone Number</label>
                  <input 
                    v-model="whatsappConfig.phone_number"
                    type="text"
                    placeholder="+1234567890"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!whatsappConfig.enabled"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Business Account ID</label>
                  <input 
                    v-model="whatsappConfig.business_account_id"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!whatsappConfig.enabled"
                  >
                </div>
              </div>

              <!-- API Configuration -->
              <div class="space-y-4">
                <h4 class="text-sm font-medium text-gray-900">API Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">API Key</label>
                    <input 
                      v-model="whatsappConfig.api_key"
                      type="password"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      :disabled="!whatsappConfig.enabled"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">API Secret</label>
                    <input 
                      v-model="whatsappConfig.api_secret"
                      type="password"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      :disabled="!whatsappConfig.enabled"
                    >
                  </div>
                </div>
              </div>

              <!-- Webhook Configuration -->
              <div>
                <label class="block text-sm font-medium text-gray-700">Webhook URL</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                  <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                    URL
                  </span>
                  <input 
                    v-model="whatsappConfig.webhook_url"
                    type="text"
                    class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="https://your-domain.com/api/whatsapp/webhook"
                    :disabled="!whatsappConfig.enabled"
                  >
                </div>
              </div>

              <!-- Message Templates -->
              <div class="space-y-4">
                <h4 class="text-sm font-medium text-gray-900">Message Templates</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Template Namespace</label>
                    <input 
                      v-model="whatsappConfig.template_namespace"
                      type="text"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      :disabled="!whatsappConfig.enabled"
                    >
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Template Language</label>
                    <select 
                      v-model="whatsappConfig.template_language"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      :disabled="!whatsappConfig.enabled"
                    >
                      <option value="en">English</option>
                      <option value="es">Spanish</option>
                      <option value="pt">Portuguese</option>
                      <option value="ar">Arabic</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Auto Reply Settings -->
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-medium text-gray-900">Auto Reply</h4>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                      type="checkbox" 
                      v-model="whatsappConfig.auto_reply" 
                      class="sr-only peer"
                      :disabled="!whatsappConfig.enabled"
                    >
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                  </label>
                </div>
                <div v-if="whatsappConfig.auto_reply">
                  <label class="block text-sm font-medium text-gray-700">Auto Reply Message</label>
                  <textarea 
                    v-model="whatsappConfig.auto_reply_message"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!whatsappConfig.enabled"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Departments Tab -->
        <div v-show="activeTab === 'departments'" class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Departments</h3>
          </div>

          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent Count</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="dept in departments" :key="dept.id">
                <td class="px-6 py-4 whitespace-nowrap">{{ dept.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ dept.email || 'Not set' }}</td>
                <td class="px-6 py-4 whitespace-nowrap">{{ dept.users_count }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="dept.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                    {{ dept.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="mt-4 text-sm text-gray-500">
            <p>* Department management is available in the Admin section</p>
            <p class="mt-1">
              <a href="/departments" class="text-indigo-600 hover:text-indigo-900">
                Go to Department Management →
              </a>
            </p>
          </div>
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

        <!-- Add Spam Tab Content -->
        <div v-show="activeTab === 'spam'" class="space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium">Spam List Management</h3>
              
              <!-- Add New Form -->
              <form @submit.prevent="addSpamContact" class="flex gap-2">
                <select v-model="newSpamContact.type"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <option value="email">Email</option>
                  <option value="phone">Phone</option>
                </select>
                <input v-model="newSpamContact.value"
                    :placeholder="newSpamContact.type === 'email' ? 'Email address' : 'Phone number'"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <input v-model="newSpamContact.reason"
                    placeholder="Reason (optional)"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                  Add
                </button>
              </form>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingSpam" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Spam List -->
            <div v-else>
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempts</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Attempt</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Added</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="contact in spamContacts" :key="contact.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">
                      <span :class="[
                          'px-2 py-1 rounded-full text-xs font-medium',
                          contact.type === 'email' 
                              ? 'bg-blue-100 text-blue-800' 
                              : 'bg-purple-100 text-purple-800'
                      ]">
                        {{ contact.type }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ contact.value }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ contact.reason || '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ contact.attempts }}</td>
                    <td class="px-6 py-4 text-sm">{{ contact.last_attempt || 'Never' }}</td>
                    <td class="px-6 py-4 text-sm">{{ contact.added_at }}</td>
                    <td class="px-6 py-4 text-sm">
                      <button @click="removeFromSpam(contact.id)"
                          class="text-red-600 hover:text-red-900">
                        Remove
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Empty State -->
              <div v-if="spamContacts.length === 0" class="text-center py-8 text-gray-500">
                No spam contacts found.
              </div>
            </div>
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