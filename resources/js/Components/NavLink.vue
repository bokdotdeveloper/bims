<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    const base =
        'flex h-full min-h-0 items-center gap-1.5 whitespace-nowrap border-b-2 px-2.5 text-xs font-medium leading-none transition duration-150 ease-in-out focus:outline-none';

    return props.active
        ? `${base} border-indigo-500 text-gray-900 dark:border-indigo-400 dark:text-white`
        : `${base} border-transparent text-gray-600 hover:border-slate-300 hover:text-gray-900 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:text-slate-100`;
});
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
