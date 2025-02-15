<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium mb-4">SMTP Outgoing Email</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">From Name</label>
          <input v-model="smtpConfig.fromName" placeholder="Support Team" :class="{'border-red-500': errors['smtp_config.fromName']}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <p v-if="errors['smtp_config.fromName']" class="mt-1 text-sm text-red-600">{{ errors['smtp_config.fromName'][0] }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Email Address</label>
          <input v-model="smtpConfig.email" placeholder="support@example.com" :class="{'border-red-500': errors['smtp_config.email']}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
          <p v-if="errors['smtp_config.email']" class="mt-1 text-sm text-red-600">{{ errors['smtp_config.email'][0] }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
          <input v-model="smtpConfig.host" placeholder="smtp.example.com" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
      </div>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">SMTP Port</label>
          <input v-model="smtpConfig.port" type="number" placeholder="587" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Encryption</label>
          <select v-model="smtpConfig.encryption" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="tls">TLS</option>
            <option value="ssl">SSL</option>
            <option value="none">None</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Username</label>
          <input v-model="smtpConfig.username" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Password</label>
          <input v-model="smtpConfig.password" type="password" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  smtpConfig: Object,
  errors: Object
});
</script>
