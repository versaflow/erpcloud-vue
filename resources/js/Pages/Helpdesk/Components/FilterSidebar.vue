<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
    departments: {
        type: Array,
        required: true,
        default: () => []
    },
    emailSources: {
        type: Array,
        required: true,
        default: () => []
    },
    agents: {
        type: Array,
        required: true,
        default: () => []
    },
    isAdmin: {
        type: Boolean,
        required: true
    }
});


const emit = defineEmits(['filter-change']);

const searchText = ref('');
const selectedDepartment = ref('all');
const selectedEmailSource = ref('all');
const selectedAgent = ref('all');
const selectedFilter = ref('inbox');

const filterOptions = [
    { 
        id: 'inbox', 
        label: 'Inbox',
        svg: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />`
    },
    { 
        id: 'archived', 
        label: 'Archived',
        svg: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />`
    },
    { 
        id: 'spam', 
        label: 'Spam',
        svg: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />`
    }
];

const departmentCount = computed(() => props.departments.length);
const agentCount = computed(() => props.agents.length);

function emitFilterChange() {
    emit('filter-change', {
        search: searchText.value,
        department: selectedDepartment.value,
        emailSource: selectedEmailSource.value,
        agent: selectedAgent.value,
        filter: selectedFilter.value
    });
}

watch(
    [searchText, selectedDepartment, selectedEmailSource, selectedAgent, selectedFilter],
    emitFilterChange
);

// Add debug watch
watch(() => props.departments, (newDepts) => {

}, { immediate: true });

watch(() => props.agents, (newAgents) => {

}, { immediate: true });

// Add debug info
watch(() => props.departments, (newDepts) => {
}, { immediate: true });

watch(selectedDepartment, (newDept) => {
}, { immediate: true });
</script>

<template>
    <div class="p-3 space-y-3">
        <!-- Add debug info -->
        <div class="text-xs text-gray-500 space-y-1 mb-3 p-2 bg-gray-50 rounded">
            <div>Departments: {{ departmentCount }}</div>
            <div v-if="selectedDepartment !== 'all'">
                Selected: {{ departments.find(d => d.id === selectedDepartment)?.name }}
            </div>
        </div>

        <!-- Search input - more compact -->
        <div class="relative">
            <input v-model="searchText" 
                   type="text"
                   class="w-full pl-8 pr-2 py-1.5 text-sm rounded-lg border"
                   placeholder="Search..." />
            <svg class="w-4 h-4 absolute left-2 top-2 text-gray-400" 
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <!-- Filters - more compact -->
        <div class="space-y-2 ">
            <!-- Department filter -->
            <div class="relative ">
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <select v-model="selectedDepartment"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All Departments</option>
                    <option v-for="dept in departments"
                            :key="dept.id"
                            :value="dept.id">
                        {{ dept.name }}
                        {{ dept.is_active ? '' : '(Inactive)' }}
                    </option>
                </select>
            
            </div>

            <!-- Email source filter -->
            <select v-model="selectedEmailSource"
                    class="w-full rounded-lg border">
                <option value="all">All Email Sources</option>
                <option v-for="source in emailSources"
                        :key="source.id"
                        :value="source.id">
                    {{ source.label }}
                </option>
            </select>

            <!-- Agent filter (admin only) -->
            <div v-if="isAdmin" class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                <select v-model="selectedAgent"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All Agents</option>
                    <option v-for="agent in agents"
                            :key="agent.id"
                            :value="agent.id">
                        {{ agent.name }} ({{ agent.department || 'No Department' }})
                    </option>
                </select>
                <div v-if="agents.length === 0" class="text-xs text-red-500 mt-1">
                    No agents available
                </div>
            </div>

            <!-- Status filters -->
            <div class="space-y-2">
                <button v-for="filter in filterOptions"
                        :key="filter.id"
                        @click="selectedFilter = filter.id"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg"
                        :class="selectedFilter === filter.id ? 'bg-indigo-600 text-white' : 'hover:bg-gray-100'">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" v-html="filter.svg"></svg>
                        {{ filter.label }}
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
