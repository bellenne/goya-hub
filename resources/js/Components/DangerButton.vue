<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicDangerButton from '@/Components/Themes/Classic/DangerButton.vue';
import ModernDangerButton from '@/Components/Themes/Modern/DangerButton.vue';

const page = usePage();

const component = computed(() => {
    return (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern'
        ? ModernDangerButton
        : ClassicDangerButton;
});
</script>

<template>
    <component :is="component" v-bind="$attrs">
        <slot />
    </component>
</template>
