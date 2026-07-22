<script setup>
defineProps({
    tone: {
        type: String,
        default: 'brass',
    },
});
</script>

<template>
    <span class="gm-badge" :class="`gm-badge-${tone}`">
        <slot />
    </span>
</template>
