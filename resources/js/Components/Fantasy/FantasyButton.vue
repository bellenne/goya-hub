<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="gm-button" :class="`gm-button-${variant}`">
        <slot />
    </button>
</template>
