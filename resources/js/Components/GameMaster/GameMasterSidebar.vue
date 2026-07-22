<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    game: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    open: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);
</script>

<template>
    <button
        type="button"
        class="gm-sidebar-scrim"
        :class="{ 'gm-sidebar-scrim-open': open }"
        aria-label="Закрыть меню"
        @click="$emit('close')"
    />

    <aside class="gm-sidebar" :class="{ 'gm-sidebar-open': open }">
        <Link :href="route('games.show', game.id)" class="gm-sidebar-brand" @click="$emit('close')">
            <span class="gm-sidebar-mark">
                <ApplicationLogo class="h-11 w-auto" />
            </span>
            <span class="min-w-0">
                <span class="gm-kicker block">Панель мастера</span>
                <span class="gm-sidebar-title block">{{ game.name }}</span>
            </span>
        </Link>

        <nav class="gm-sidebar-nav">
            <Link
                v-for="item in items"
                :key="item.label"
                :href="item.href"
                class="gm-sidebar-link"
                :class="{ 'gm-sidebar-link-active': item.active }"
                @click="$emit('close')"
            >
                <span class="gm-sidebar-link-icon">
                    <img :src="item.icon" alt="" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block">{{ item.label }}</span>
                    <span v-if="item.description" class="gm-sidebar-link-description">{{ item.description }}</span>
                </span>
            </Link>
        </nav>

        <div class="gm-sidebar-note">
            <div class="flex items-center gap-3">
                <span class="gm-sidebar-note-icon">
                    <img src="/storage/ui/icons/Notes.png" alt="" />
                </span>
                <p class="gm-kicker">Текущий мир</p>
            </div>
            <p class="mt-3 text-sm font-semibold leading-6 text-[#fff1c8]">{{ game.name }}</p>
        </div>
    </aside>
</template>
