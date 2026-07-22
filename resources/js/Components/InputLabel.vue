<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicInputLabel from '@/Components/Themes/Classic/InputLabel.vue';
import ModernInputLabel from '@/Components/Themes/Modern/InputLabel.vue';

defineProps({
    value: {
        type: String,
    },
});

const page = usePage();
const component = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern' ? ModernInputLabel : ClassicInputLabel);
</script>

<template>
    <component :is="component" :value="value" v-bind="$attrs">
        <slot />
    </component>
</template>
