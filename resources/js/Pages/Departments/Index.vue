<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Departments Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Departments List</h3>
                            <button
                                @click="openModal()"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                            >
                                Add Department
                            </button>
                        </div>

                        <!-- Departments Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Agent Count</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="department in departments" :key="department.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ department.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ department.users_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'px-2 py-1 text-xs rounded-full',
                                                department.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                            ]">
                                                {{ department.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button 
                                                @click="openModal(department)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-4"
                                            >
                                                Edit
                                            </button>
                                            <button 
                                                v-if="department.users_count === 0"
                                                @click="deleteDepartment(department.id)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Create/Edit Modal -->
                        <Modal :show="showModal" @close="closeModal">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    {{ editingDepartment ? 'Edit Department' : 'Create Department' }}
                                </h3>
                                <form @submit.prevent="saveDepartment">
                                    <div>
                                        <InputLabel for="name" value="Department Name" />
                                        <TextInput
                                            id="name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.name"
                                            required
                                            autofocus
                                        />
                                        <InputError :message="form.errors.name" class="mt-2" />
                                    </div>

                                    <div class="mt-4">
                                        <label class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                v-model="form.is_active"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-600">Active</span>
                                        </label>
                                        <InputError :message="form.errors.is_active" class="mt-2" />
                                    </div>
                                    
                                    <div class="mt-6 flex justify-end gap-4">
                                        <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </Modal>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    departments: Array
});

const showModal = ref(false);
const editingDepartment = ref(null);

const form = useForm({
    name: '',
    is_active: true
});

const openModal = (department = null) => {
    editingDepartment.value = department;
    if (department) {
        form.name = department.name;
        form.is_active = department.is_active;
    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingDepartment.value = null;
    form.reset();
};

const saveDepartment = () => {
    if (editingDepartment.value) {
        form.put(route('departments.update', editingDepartment.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('departments.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const deleteDepartment = (id) => {
    if (confirm('Are you sure you want to delete this department?')) {
        form.delete(route('departments.destroy', id));
    }
};
</script>
