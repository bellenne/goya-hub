<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select
        class="gm-select"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <slot />
    </select>
</template>
