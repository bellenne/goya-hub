<script setup>
import GameMasterHeader from '@/Components/GameMaster/GameMasterHeader.vue';
import GameMasterSidebar from '@/Components/GameMaster/GameMasterSidebar.vue';
import { ref } from 'vue';

defineProps({
    game: {
        type: Object,
        required: true,
    },
    navigation: {
        type: Array,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const sidebarOpen = ref(false);
</script>

<template>
    <div class="classic-shell gm-shell">
        <GameMasterSidebar
            :game="game"
            :items="navigation"
            :open="sidebarOpen"
            @close="sidebarOpen = false"
        />

        <div class="gm-workspace">
            <GameMasterHeader
                :title="title"
                :subtitle="subtitle"
                @menu="sidebarOpen = true"
            />

            <main class="gm-main">
                <slot />
            </main>
        </div>
    </div>
</template>
