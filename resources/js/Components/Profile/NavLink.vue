<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'link link-hover inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 text-base transition duration-150 ease-in-out'
        : 'link link-hover inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 transition duration-150 ease-in-out';
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
