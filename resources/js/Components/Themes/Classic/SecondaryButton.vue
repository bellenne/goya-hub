<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="classic-button classic-button-secondary">
        <slot />
    </button>
</template>
