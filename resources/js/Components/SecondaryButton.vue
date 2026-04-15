<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-md border border-amber-300/25 bg-stone-950/65 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-stone-200 shadow-sm transition duration-150 ease-in-out hover:border-amber-300/45 hover:bg-stone-800 hover:text-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-300/40 focus:ring-offset-2 focus:ring-offset-stone-950 disabled:cursor-not-allowed disabled:opacity-50"
    >
        <slot />
    </button>
</template>
