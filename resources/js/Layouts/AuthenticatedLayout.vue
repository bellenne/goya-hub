<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicAuthenticatedLayout from '@/Layouts/Themes/ClassicAuthenticatedLayout.vue';
import ModernAuthenticatedLayout from '@/Layouts/Themes/ModernAuthenticatedLayout.vue';

const page = usePage();

const layout = computed(() => {
    return (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern'
        ? ModernAuthenticatedLayout
        : ClassicAuthenticatedLayout;
});
</script>

<template>
    <component :is="layout">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </component>
</template>
