<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicGuestLayout from '@/Layouts/Themes/ClassicGuestLayout.vue';
import ModernGuestLayout from '@/Layouts/Themes/ModernGuestLayout.vue';

const page = usePage();

const layout = computed(() => {
    return (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern'
        ? ModernGuestLayout
        : ClassicGuestLayout;
});
</script>

<template>
    <component :is="layout">
        <slot />
    </component>
</template>
