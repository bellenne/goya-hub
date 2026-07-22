<script setup>
import FantasyPanel from '@/Components/Fantasy/FantasyPanel.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    actions: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <FantasyPanel title="Быстрые действия" kicker="Инструменты" icon="/storage/ui/icons/Add.png" dense>
        <div v-if="actions.length" class="grid gap-3 sm:grid-cols-2">
            <Link v-for="action in actions" :key="action.label" :href="action.href" class="gm-action-card group">
                <span class="gm-action-icon">
                    <img :src="action.icon" alt="" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-[#fff1c8]">{{ action.label }}</span>
                    <span class="mt-1 block text-xs leading-5 text-[#ad9a78]">{{ action.description }}</span>
                </span>
                <span class="gm-action-arrow">→</span>
            </Link>
        </div>
        <div v-else class="gm-empty">
            Доступных быстрых действий пока нет.
        </div>
    </FantasyPanel>
</template>
