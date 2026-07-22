<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    kicker: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: '',
    },
    dense: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section class="gm-panel" :class="{ 'gm-panel-dense': dense }">
        <div v-if="title || kicker || icon || $slots.actions" class="gm-panel-head">
            <div class="flex min-w-0 items-start gap-3">
                <span v-if="icon" class="gm-panel-icon">
                    <img :src="icon" alt="" />
                </span>
                <div class="min-w-0">
                    <p v-if="kicker" class="gm-kicker">{{ kicker }}</p>
                    <h2 v-if="title" class="gm-panel-title">{{ title }}</h2>
                </div>
            </div>
            <div v-if="$slots.actions" class="gm-panel-actions">
                <slot name="actions" />
            </div>
        </div>

        <div :class="{ 'mt-5': title || kicker || icon || $slots.actions }">
            <slot />
        </div>
    </section>
</template>
