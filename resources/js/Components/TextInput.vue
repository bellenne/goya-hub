<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ClassicTextInput from '@/Components/Themes/Classic/TextInput.vue';
import ModernTextInput from '@/Components/Themes/Modern/TextInput.vue';

const model = defineModel({
    type: String,
    required: true,
});

const page = usePage();
const input = ref(null);

const component = computed(() => {
    return (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern'
        ? ModernTextInput
        : ClassicTextInput;
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <component :is="component" ref="input" v-model="model" v-bind="$attrs" />
</template>
