<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicSecondaryButton from '@/Components/Themes/Classic/SecondaryButton.vue';
import ModernSecondaryButton from '@/Components/Themes/Modern/SecondaryButton.vue';

defineProps({
    type: {
        type: String,
        default: 'button',
    },
});

const page = usePage();

const component = computed(() => {
    return (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern'
        ? ModernSecondaryButton
        : ClassicSecondaryButton;
});
</script>

<template>
    <component :is="component" :type="type" v-bind="$attrs">
        <slot />
    </component>
</template>
