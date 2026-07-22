<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <article class="gm-stat">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p>{{ label }}</p>
                <strong>{{ value }}</strong>
            </div>
            <span v-if="icon" class="gm-stat-icon">
                <img :src="icon" alt="" />
            </span>
        </div>
    </article>
</template>
