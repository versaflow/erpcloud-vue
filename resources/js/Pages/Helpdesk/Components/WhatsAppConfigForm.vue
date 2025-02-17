<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-medium">WhatsApp Business API</h3>
        <p class="text-sm text-gray-500 mt-1">Last webhook received: {{ whatsappConfig.last_webhook_received || 'No webhooks yet' }}</p>
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
          <input v-model="whatsappConfig.phone_number" type="text" placeholder="+1234567890" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Business Account ID</label>
          <input v-model="whatsappConfig.business_account_id" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled">
        </div>
      </div>

      <!-- API Configuration -->
      <div class="space-y-4">
        <h4 class="text-sm font-medium text-gray-900">API Configuration</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">API Key</label>
            <input v-model="whatsappConfig.api_key" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">API Secret</label>
            <input v-model="whatsappConfig.api_secret" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled">
          </div>
        </div>
      </div>

      <!-- Webhook Configuration -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Webhook URL</label>
        <div class="mt-1 flex rounded-md shadow-sm">
          <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">URL</span>
          <input v-model="whatsappConfig.webhook_url" type="text" class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://your-domain.com/api/whatsapp/webhook" :disabled="!whatsappConfig.enabled">
        </div>
      </div>

      <!-- Message Templates -->
      <div class="space-y-4">
        <h4 class="text-sm font-medium text-gray-900">Message Templates</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Template Namespace</label>
            <input v-model="whatsappConfig.template_namespace" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Template Language</label>
            <select v-model="whatsappConfig.template_language" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled">
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
            <input type="checkbox" v-model="whatsappConfig.auto_reply" class="sr-only peer" :disabled="!whatsappConfig.enabled">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
          </label>
        </div>
        <div v-if="whatsappConfig.auto_reply">
          <label class="block text-sm font-medium text-gray-700">Auto Reply Message</label>
          <textarea v-model="whatsappConfig.auto_reply_message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :disabled="!whatsappConfig.enabled"></textarea>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  whatsappConfig: Object
});
</script>