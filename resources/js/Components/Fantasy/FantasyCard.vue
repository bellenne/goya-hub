<script setup>
defineProps({
    interactive: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <article class="gm-card" :class="{ 'gm-card-interactive': interactive }">
        <slot />
    </article>
</template>
