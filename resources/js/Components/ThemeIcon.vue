<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    src: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    alt: {
        type: String,
        default: '',
    },
});

const page = usePage();
const isClassicTheme = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') !== 'modern');

const iconKey = computed(() => {
    if (props.name) {
        return props.name.toLowerCase();
    }

    return (props.src.split('/').pop() ?? '')
        .replace(/\.[^.]+$/, '')
        .toLowerCase();
});

const paths = {
    add: ['M12 5v14', 'M5 12h14'],
    backgrounds: ['M4 6h16v12H4z', 'M8 14l2.5-3 2 2.5L15 10l3 4'],
    characters: ['M16 21v-2a4 4 0 0 0-8 0v2', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8'],
    edit: ['M12 20h9', 'M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z'],
    filter: ['M4 5h16', 'M7 12h10', 'M10 19h4'],
    inventory: ['M4 7h16v13H4z', 'M8 7a4 4 0 0 1 8 0', 'M9 12h6'],
    management: ['M12 3v18', 'M3 12h18', 'M6 6l12 12', 'M18 6 6 18'],
    notes: ['M6 3h9l3 3v15H6z', 'M14 3v5h5', 'M9 13h6', 'M9 17h5'],
    notifications: ['M18 16H6l1.5-2V9a4.5 4.5 0 0 1 9 0v5Z', 'M10 19a2 2 0 0 0 4 0'],
    players: ['M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2', 'M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8', 'M21 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75'],
    profile: ['M20 21a8 8 0 0 0-16 0', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8'],
    remove: ['M5 12h14'],
    search: ['M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16', 'm21 21-4.35-4.35'],
    sessions: ['M7 3v4', 'M17 3v4', 'M4 7h16', 'M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z'],
    settings: ['M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z', 'M19.4 15a1.8 1.8 0 0 0 .36 1.98l.04.05-1.7 2.94-.06-.02a1.8 1.8 0 0 0-2.05.1 8 8 0 0 1-1.7.98 1.8 1.8 0 0 0-1.25 1.56V23h-3.4v-.07a1.8 1.8 0 0 0-1.24-1.56 8 8 0 0 1-1.7-.98 1.8 1.8 0 0 0-2.06-.1l-.06.02-1.7-2.94.04-.05A1.8 1.8 0 0 0 4.6 15a8 8 0 0 1 0-2 1.8 1.8 0 0 0-.36-1.98l-.04-.05 1.7-2.94.06.02a1.8 1.8 0 0 0 2.05-.1 8 8 0 0 1 1.7-.98 1.8 1.8 0 0 0 1.25-1.56V5h3.4v.07a1.8 1.8 0 0 0 1.24 1.56 8 8 0 0 1 1.7.98 1.8 1.8 0 0 0 2.06.1l.06-.02 1.7 2.94-.04.05A1.8 1.8 0 0 0 19.4 13a8 8 0 0 1 0 2Z'],
    tickets: ['M4 7a2 2 0 0 1 2-2h12v4a2 2 0 0 0 0 4v4H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z', 'M9 9h4', 'M9 15h6'],
    viewing: ['M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
};

const modernPaths = computed(() => paths[iconKey.value] ?? paths.viewing);
</script>

<template>
    <img v-if="isClassicTheme && src" :src="src" :alt="alt" />
    <svg
        v-else
        aria-hidden="true"
        class="theme-modern-icon"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path v-for="path in modernPaths" :key="path" :d="path" />
    </svg>
</template>
