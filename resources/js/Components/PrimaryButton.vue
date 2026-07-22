<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicPrimaryButton from '@/Components/Themes/Classic/PrimaryButton.vue';
import ModernPrimaryButton from '@/Components/Themes/Modern/PrimaryButton.vue';

const page = usePage();

const component = computed(() => {
    return (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern'
        ? ModernPrimaryButton
        : ClassicPrimaryButton;
});
</script>

<template>
    <component :is="component" v-bind="$attrs">
        <slot />
    </component>
</template>
