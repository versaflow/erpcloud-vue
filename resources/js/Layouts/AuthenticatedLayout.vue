<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import SideNav from '@/Layouts/SideNav.vue';
import { Link } from '@inertiajs/vue3';
import { useSidebar } from '@/Composables/useSidebar.js';
import Toast from '@/Components/Toast.vue';

const showingNavigationDropdown = ref(false);
const { isCollapsed } = useSidebar();


const mainContentClass = computed(() => ({
  'main-content': true,
  'content-collapsed': isCollapsed.value
}));
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-end">
                        <div class="hidden sm:flex sm:items-center">
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}
                                                <i class="fas fa-chevron-down ml-2"></i>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                                <i :class="[
                                    'fas',
                                    showingNavigationDropdown ? 'fa-times' : 'fa-bars'
                                ]"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden">
                    <v-navigation-drawer
                        v-model="showingNavigationDropdown"
                        location="right"
                        temporary
                        class="sm:hidden"
                    >
                        <v-list>
                            <v-list-item>
                                <v-list-item-title class="text-subtitle-1">
                                    {{ $page.props.auth.user.name }}
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ $page.props.auth.user.email }}
                                </v-list-item-subtitle>
                            </v-list-item>

                            <v-divider></v-divider>

                            <v-list-item :href="route('profile.edit')">
                                <v-list-item-title>Profile</v-list-item-title>
                            </v-list-item>
                            <v-list-item :href="route('logout')" method="post" as="button">
                                <v-list-item-title>Log Out</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-navigation-drawer>
                </div>
            </nav>

            <div class="dashboard-layout">
                <SideNav />
                <!-- Page Content -->
                <main :class="mainContentClass" s>
                    <slot />
                </main>
            </div>
        </div>
        <Toast /> 
    </div>
</template>

<style scoped>
.dashboard-layout {
    display: flex;
    min-height: 100vh;
    position: relative;
}

.main-content {
    flex: 1;
    margin-left: 256px;
    transition: all 0.3s ease;
    width: calc(100% - 256px);
}

.content-collapsed {
    margin-left: 64px;
    width: calc(100% - 64px);
}

:deep(.v-btn) {
  text-transform: none;
}
</style>
