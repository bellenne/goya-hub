<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicInputError from '@/Components/Themes/Classic/InputError.vue';
import ModernInputError from '@/Components/Themes/Modern/InputError.vue';

defineProps({
    message: {
        type: String,
    },
});

const page = usePage();
const component = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern' ? ModernInputError : ClassicInputError);
</script>

<template>
    <component :is="component" :message="message" v-bind="$attrs" />
</template>
