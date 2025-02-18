<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, onMounted } from 'vue';
import { useSidebar } from '@/Composables/useSidebar.js';
import { useToast } from '@/Composables/useToast';
import EmailConfigForm from './Components/EmailConfigForm.vue';
import WhatsAppConfigForm from './Components/WhatsAppConfigForm.vue';
import Toast from '@/Components/Toast.vue';

// Initialize toast
const toast = useToast();

// Update the props definition
const props = defineProps({
    departments: {
        type: Array,
        required: true,
        default: () => []
    },
    emailSettings: {
        type: Array,
        required: true,
        default: () => []
    }
});

const { isCollapsed } = useSidebar();
const activeTab = ref('email');

// Track form changes
const hasChanges = ref(false);
const isSaving = ref(false);
const isSyncing = ref(false);

// Initialize the component state
const emailConfigs = ref(props.emailSettings.map(setting => ({
    ...setting,
    imap_settings: typeof setting.imap_settings === 'string' 
        ? JSON.parse(setting.imap_settings)
        : setting.imap_settings || {
            encryption: 'ssl',
            validate_cert: true
        },
    smtp_config: {
        from_name: setting.smtp_setting?.from_name || '',
        email: setting.smtp_setting?.email || '',
        host: setting.smtp_setting?.host || '',
        port: setting.smtp_setting?.port || '',
        username: setting.smtp_setting?.username || '',
        password: setting.smtp_setting?.password || '',
        encryption: setting.smtp_setting?.encryption || 'tls'
    },
    signatures: setting.signatures || [{
        name: 'Default',
        content: 'Best regards,\n{name}\n{position}\n{department}',
        isDefault: true
    }]
})));

const addEmailConfig = () => {
    if (emailConfigs.value.length < 5) {
        emailConfigs.value.push({
            id: null,
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
            },
            smtp_config: {
                from_name: '',
                email: '',
                host: '',
                port: '',
                username: '',
                password: '',
                encryption: 'tls'
            },
            signatures: [{
                name: 'Default',
                content: 'Best regards,\n{name}\n{position}\n{department}',
                isDefault: true
            }]
        });
    }
};

const removeItem = (array, index) => array.splice(index, 1);

const updateImapConfig = (config) => {
    hasChanges.value = true;
};

// Change from single isSyncing to a Map
const syncingStates = ref(new Map());

const syncEmails = async (imapConfig) => {
    if (!imapConfig.id) {
        alert('Please save the email settings first before syncing.');
        return;
    }

    syncingStates.value.set(imapConfig.id, true);
    try {
        const response = await axios.post(route('helpdesk.email.sync'), {
            email_id: imapConfig.id
        });
        
        imapConfig.last_sync_at = response.data.last_sync;
        showSuccess('Email sync completed successfully');
    } catch (error) {
        console.error('Sync failed:', error);
        alert('Failed to sync emails: ' + (error.response?.data?.error || error.message));
    } finally {
        syncingStates.value.set(imapConfig.id, false);
    }
};

// Add helper function to check sync state
const isConfigSyncing = (configId) => syncingStates.value.get(configId) || false;

const errors = ref({});
const showError = ref(false);

const showSuccess = (message) => {
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: message,
        life: 3000
    });
};

const showErrorT = (message) => {
    toast.add({
        severity: 'error',
        summary: 'Error',
        detail: message,
        life: 5000
    });
};

const saveSettings = async () => {
    errors.value = {};
    showError.value = false;
    isSaving.value = true;
    
    try {
        const { data } = await axios.post(route('helpdesk.settings.save'), {
            imap_configs: emailConfigs.value.map(config => ({
                ...config,
                imap_settings: {
                    encryption: config.imap_settings.encryption || 'ssl',
                    validate_cert: config.imap_settings.validate_cert ?? true
                },
                smtp_config: {
                    from_name: config.smtp_config.from_name,
                    email: config.smtp_config.email,
                    host: config.smtp_config.host,
                    port: config.smtp_config.port,
                    username: config.smtp_config.username,
                    password: config.smtp_config.password,
                    encryption: config.smtp_config.encryption
                },
                signatures: config.signatures.map(sig => ({
                    name: sig.name,
                    content: sig.content,
                    isDefault: sig.isDefault ?? false
                }))
            }))
        });

        if (data.imap_accounts) {
            emailConfigs.value = data.imap_accounts.map(account => ({
                ...account,
                imap_settings: typeof account.imap_settings === 'string'
                    ? JSON.parse(account.imap_settings)
                    : account.imap_settings,
                smtp_config: {
                    from_name: account.smtp_setting?.from_name || '',
                    email: account.smtp_setting?.email || '',
                    host: account.smtp_setting?.host || '',
                    port: account.smtp_setting?.port || '',
                    username: account.smtp_setting?.username || '',
                    password: account.smtp_setting?.password || '',
                    encryption: account.smtp_setting?.encryption || 'tls'
                },
                signatures: account.signatures || []
            }));
        }
        
        hasChanges.value = false;
        showSuccess(data.message || 'Settings saved successfully');
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
            
            // Extract unique config numbers from error keys
            const configNums = new Set();
            Object.keys(error.response.data.errors).forEach(key => {
          const match = key.match(/imap_configs\.(\d+)\./);
          if (match) configNums.add(parseInt(match[1]) + 1);
            });
            
            // Convert to sorted array and format message
            const configList = Array.from(configNums).sort((a, b) => a - b);
            const configStr = configList.length > 1 
          ? `configurations ${configList.join(', ')}` 
          : `configuration ${configList[0]}`;
          
            showErrorT(`Please check email ${configStr}`);
        }
        console.error('Error saving settings:', error);
    } finally {
        isSaving.value = false;
    }
};

watch(emailConfigs, () => {
    hasChanges.value = true;
}, { deep: true });

const queueStatus = ref({
    jobs: 0,
    failed_jobs: 0,
    workers_running: false,
    last_job_processed: null
});



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
    setInterval(fetchCronStatus, 60000);
});

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
    last_webhook_received: null
});

const tabs = ['email', 'departments', 'social', 'spam', 'general'];

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
        <Toast /> <!-- Make sure this is inside AuthenticatedLayout -->
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
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-700">Email Sync Status</h3>
                            <span class="text-sm text-gray-500">
                                Last Run: {{ cronStatus.last_run || 'Never' }}
                            </span>
                        </div>
                    </div>
                    
                    <EmailConfigForm
                        :configs="emailConfigs"
                        :departments="departments"
                        :syncingStates="syncingStates"
                        :errors="errors"
                        @addConfig="addEmailConfig"
                        @removeConfig="index => emailConfigs.splice(index, 1)"
                        @syncEmails="syncEmails"
                    />
                </div>

                <!-- Add Social Integration Tab -->
                <div v-show="activeTab === 'social'" class="space-y-6">
                    <WhatsAppConfigForm
                        :whatsappConfig="whatsappConfig"
                    />
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
                        <!-- ... other general settings ... -->
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