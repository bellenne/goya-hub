<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicCheckbox from '@/Components/Themes/Classic/Checkbox.vue';
import ModernCheckbox from '@/Components/Themes/Modern/Checkbox.vue';

defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const emit = defineEmits(['update:checked']);

const page = usePage();
const component = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern' ? ModernCheckbox : ClassicCheckbox);
</script>

<template>
    <component :is="component" :checked="checked" :value="value" v-bind="$attrs" @update:checked="emit('update:checked', $event)" />
</template>
