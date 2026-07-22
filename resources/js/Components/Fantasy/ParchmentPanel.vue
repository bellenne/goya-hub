<script setup>
defineProps({
    darkText: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="gm-parchment" :class="{ 'gm-parchment-ink': darkText }">
        <slot />
    </div>
</template>
