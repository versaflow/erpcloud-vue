<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Support" />

  <AuthenticatedLayout>
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h2 class="text-xl font-semibold text-gray-800">Support Center</h2>
      </div>
    </header>

    <main class="p-6">
      <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h3 class="text-lg font-medium mb-4">Support Tickets</h3>
          <!-- Add your support content here -->
        </div>
      </div>
    </main>
  </AuthenticatedLayout>
</template>
