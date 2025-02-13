<template>
  <nav 
    :class="[
      'fixed left-0 top-0 h-screen bg-white border-r border-gray-200 transition-all duration-300 shadow-sm z-50',
      isCollapsed ? 'w-16' : 'w-64'
    ]"
    @mouseenter="expandOnHover"
    @mouseleave="collapseOnLeave"
  >
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
      <Link :href="route('dashboard')" class="shrink-0">
        <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
      </Link>
      <button @click="toggleSidebar" 
        class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500">
        <i :class="[
          'fas transition-transform duration-200',
          isCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'
        ]"></i>
      </button>
    </div>
    
    <div class="flex flex-col h-[calc(100vh-4rem)]">
      <!-- Main Navigation -->
      <div class="flex-1 p-4">
        <ul class="space-y-1">
          <!-- Dashboard -->
          <li>
            <Link 
              :href="route('dashboard')" 
              :class="[
                'flex items-center px-2 py-2 rounded-lg transition-colors group',
                route().current('dashboard')
                  ? 'bg-gray-100 text-gray-900'
                  : 'text-gray-600 hover:bg-gray-50'
              ]"
            >
              <i class="fas fa-home w-5 h-5"></i>
              <span :class="['ml-3 whitespace-nowrap transition-opacity duration-200', isCollapsed ? 'opacity-0 group-hover:opacity-100' : '']">
                Dashboard
              </span>
            </Link>
          </li>

          <!-- Helpdesk Section -->
          <li class="relative">
            <div 
              @click="handleHelpdeskClick" 
              :class="[
                'flex items-center justify-between px-2 py-2 rounded-lg cursor-pointer transition-colors group',
                (isHelpdeskSection || isHelpdeskOpen)
                  ? 'bg-gray-100 text-gray-900'
                  : 'text-gray-600 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center min-w-0">
                <i class="fas fa-headset w-5 h-5 flex-shrink-0"></i>
                <span class="ml-3 whitespace-nowrap" :class="{ 'invisible': isCollapsed }">
                  Helpdesk
                </span>
              </div>
              <i v-if="!isCollapsed" :class="[
                'fas fa-chevron-down transition-transform duration-200',
                { 'transform rotate-180': isHelpdeskOpen }
              ]"></i>
            </div>

            <div v-if="!isCollapsed && isHelpdeskOpen" 
                 class="mt-1 ml-7 space-y-1">
              <Link 
                v-for="item in helpdeskItems"
                :key="item.path"
                :href="route(item.route)"
                :class="[
                  'flex items-center px-2 py-2 text-sm rounded-lg transition-colors',
                  route().current(item.route)
                    ? 'bg-gray-100 text-gray-900'
                    : 'text-gray-500 hover:bg-gray-50'
                ]"
              >
                <i :class="['w-5 h-5', item.icon]"></i>
                <span class="ml-2">{{ item.name }}</span>
              </Link>
            </div>
          </li>
        </ul>
      </div>

      <!-- Admin Section at Bottom -->
      <div class="p-4 border-t border-gray-200" v-if="user.is_admin">
        <ul class="space-y-1">
          <li>
            <Link 
              :href="route('users.index')"
              :class="[
                'flex items-center px-2 py-2 rounded-lg transition-colors group',
                route().current('users.*')
                  ? 'bg-gray-100 text-gray-900'
                  : 'text-gray-600 hover:bg-gray-50'
              ]"
            >
              <i class="fas fa-users w-5 h-5"></i>
              <span :class="['ml-3 whitespace-nowrap transition-opacity duration-200', isCollapsed ? 'opacity-0 group-hover:opacity-100' : '']">
                Users
              </span>
            </Link>
          </li>
          
          <li>
            <Link 
              :href="route('departments.index')"
              :class="[
                'flex items-center px-2 py-2 rounded-lg transition-colors group',
                route().current('departments.*')
                  ? 'bg-gray-100 text-gray-900'
                  : 'text-gray-600 hover:bg-gray-50'
              ]"
            >
              <i class="fas fa-building w-5 h-5"></i>
              <span :class="['ml-3 whitespace-nowrap transition-opacity duration-200', isCollapsed ? 'opacity-0 group-hover:opacity-100' : '']">
                Departments
              </span>
            </Link>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { useSidebar } from '@/Composables/useSidebar.js'

const page = usePage()
const user = computed(() => page.props.auth.user)
const { isCollapsed, toggleSidebar } = useSidebar()
const isHelpdeskOpen = ref(false)
const wasCollapsed = ref(false)

// Auto-open helpdesk section when on helpdesk pages and not collapsed
watch([() => page.url, isCollapsed], ([newUrl, collapsed]) => {
  if (newUrl.startsWith('/helpdesk') && !collapsed) {
    isHelpdeskOpen.value = true
  }
}, { immediate: true })

const expandOnHover = () => {
  if (isCollapsed.value) {
    wasCollapsed.value = true
  }
}

const collapseOnLeave = () => {
  if (wasCollapsed.value) {
    isCollapsed.value = true
    wasCollapsed.value = false
  }
}

const handleHelpdeskClick = () => {
  if (isCollapsed.value) {
    isCollapsed.value = false
    isHelpdeskOpen.value = true
  } else {
    isHelpdeskOpen.value = !isHelpdeskOpen.value
  }
}

const isHelpdeskSection = computed(() => {
  return page.url.startsWith('/helpdesk')
})

const helpdeskItems = computed(() => {
    const items = [
        {
            name: 'Support',
            icon: 'fas fa-life-ring',
            route: 'helpdesk.support',
            path: '/helpdesk/support'
        },
        {
            name: 'Custormers',
            icon: 'fas fa-users',
            route: 'helpdesk.users',
            path: '/helpdesk/users'
        }
    ];

    if (user.value.is_admin) {
        items.push({
            name: 'Settings',
            icon: 'fas fa-cog',
            route: 'helpdesk.settings',
            path: '/helpdesk/settings'
        });
    }

    return items;
});
</script>

<style scoped>
nav {
  transition: width 0.3s ease;
}

nav:not(:hover) .invisible {
  visibility: hidden;
  opacity: 0;
  transition: visibility 0s, opacity 0.2s linear;
}

nav:hover .invisible {
  visibility: visible;
  opacity: 1;
}

.group:hover .group-hover\:opacity-100 {
  transition-delay: 0.1s;
}
</style>
