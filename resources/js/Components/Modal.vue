<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicModal from '@/Components/Themes/Classic/Modal.vue';
import ModernModal from '@/Components/Themes/Modern/Modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const page = usePage();
const component = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern' ? ModernModal : ClassicModal);
</script>

<template>
    <component :is="component" :show="show" :max-width="maxWidth" :closeable="closeable" v-bind="$attrs" @close="emit('close')">
        <slot />
    </component>
</template>
