<template>
  <div class="mt-6 pt-6 border-t border-gray-200">
    <div class="flex justify-between items-center mb-4">
      <h4 class="text-lg font-medium">Email Signatures</h4>
      <button @click="addSignature" class="bg-indigo-600 text-white px-3 py-2 text-sm rounded-lg hover:bg-indigo-700">Add Signature</button>
    </div>

    <div class="space-y-6">
      <div v-for="(signature, index) in signatures" :key="signature.id" class="border rounded-lg p-4 bg-gray-50">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-4">
            <input v-model="signature.name" placeholder="Signature Name" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <label class="flex items-center">
              <input type="radio" :checked="signature.isDefault" @change="setDefaultSignature(index)" class="rounded-full border-gray-300 text-indigo-600 focus:ring-indigo-500">
              <span class="ml-2 text-sm text-gray-600">Set as Default</span>
            </label>
          </div>
          <button v-if="!signature.isDefault" @click="removeSignature(index)" class="text-red-600 hover:text-red-700">Remove</button>
        </div>

        <div class="mt-3">
          <textarea v-model="signature.content" rows="4" placeholder="Enter your signature here... HTML supported" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        </div>

        <!-- Signature Preview -->
        <div v-if="signature.content" class="mt-3 p-3 border rounded bg-white">
          <div class="text-sm text-gray-500 mb-2">Preview:</div>
          <div v-html="signature.content" class="prose prose-sm max-w-none"></div>
        </div>

        <!-- Signature Variables Help -->
        <div class="mt-3 text-sm text-gray-500">
          Available variables:
          <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">{name}, {email}, {department}, {position}</code>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  signatures: Array
});

const emit = defineEmits(['addSignature', 'removeSignature', 'setDefaultSignature']);

const addSignature = () => emit('addSignature');
const removeSignature = (index) => emit('removeSignature', index);
const setDefaultSignature = (index) => emit('setDefaultSignature', index);
</script>
