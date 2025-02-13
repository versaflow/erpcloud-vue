<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- User Details -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">User Information</h3>
                        <form @submit.prevent="form.put(route('users.update', user.id))">
                            <div class="grid grid-cols-1 gap-6">
                                <!-- Same fields as Create but prefilled -->
                                <div>
                                    <InputLabel for="name" value="Name" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="department" value="Department" />
                                    <select
                                        id="department"
                                        v-model="form.department_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">Select Department</option>
                                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.department_id" class="mt-2" />
                                </div>

                                <div class="flex items-center gap-4">
                                    <label class="flex items-center">
                                        <Checkbox v-model:checked="form.is_admin" />
                                        <span class="ml-2">Admin</span>
                                    </label>
                                    <label class="flex items-center">
                                        <Checkbox v-model:checked="form.is_agent" />
                                        <span class="ml-2">Agent</span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-end gap-4">
                                    <Link
                                        :href="route('users.index')"
                                        class="text-gray-600 hover:text-gray-900"
                                    >
                                        Cancel
                                    </Link>
                                    <PrimaryButton :disabled="form.processing">
                                        Update User
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                        <form @submit.prevent="passwordForm.put(route('users.password.update', user.id))">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="password" value="New Password" />
                                    <TextInput
                                        id="password"
                                        v-model="passwordForm.password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="passwordForm.errors.password" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="password_confirmation" value="Confirm Password" />
                                    <TextInput
                                        id="password_confirmation"
                                        v-model="passwordForm.password_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                </div>

                                <div class="flex items-center justify-end">
                                    <PrimaryButton :disabled="passwordForm.processing">
                                        Update Password
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    departments: Array
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    department_id: props.user.department_id,
    is_admin: props.user.is_admin,
    is_agent: props.user.is_agent,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});
</script>
