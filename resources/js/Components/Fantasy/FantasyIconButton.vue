<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="gm-icon-button" :aria-label="label" :title="label">
        <img v-if="icon" :src="icon" alt="" />
        <slot v-else />
    </button>
</template>
