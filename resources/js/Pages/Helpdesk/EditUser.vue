<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const form = ref({
    name: props.user.name,
    email: props.user.email,
    tags: props.user.tags || [],
    company: props.user.company || '',
    phone: props.user.phone || '',
    location: props.user.location || '',
    timezone: props.user.timezone || '',
    notes: props.user.notes || ''
});

const newTag = ref('');

const addTag = () => {
    if (newTag.value && !form.value.tags.includes(newTag.value)) {
        form.value.tags.push(newTag.value);
        newTag.value = '';
    }
};

const removeTag = (tag) => {
    form.value.tags = form.value.tags.filter(t => t !== tag);
};

const submit = () => {
    router.put(route('helpdesk.users.update', props.user.id), form.value);
};
</script>

<template>
    <AuthenticatedLayout>
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="text-xl font-semibold text-gray-800">Edit User</h2>
            </div>
        </header>

        <main class="p-6">
            <div class="max-w-7xl mx-auto">
                <form @submit.prevent="submit" class="bg-white shadow-sm rounded-lg p-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input v-model="form.name" type="text" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input v-model="form.email" type="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Tags Section -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span v-for="tag in form.tags" :key="tag"
                                class="px-2 py-1 bg-gray-100 rounded-full text-sm flex items-center">
                                {{ tag }}
                                <button type="button" @click="removeTag(tag)"
                                    class="ml-1 text-gray-500 hover:text-gray-700">×</button>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <input v-model="newTag"
                                @keyup.enter.prevent="addTag"
                                placeholder="Add a tag"
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="button" @click="addTag"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                                Add
                            </button>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Company</label>
                            <input v-model="form.company" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input v-model="form.phone" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input v-model="form.location" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Timezone</label>
                            <input v-model="form.timezone" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea v-model="form.notes" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="router.get(route('helpdesk.users'))"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </AuthenticatedLayout>
</template>
