<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message" class="classic-error">
        {{ message }}
    </div>
</template>
