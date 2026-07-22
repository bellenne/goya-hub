<script setup>
import FantasyPanel from '@/Components/Fantasy/FantasyPanel.vue';
import ParchmentPanel from '@/Components/Fantasy/ParchmentPanel.vue';

defineProps({
    game: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <FantasyPanel title="Обзор кампании" kicker="Состояние игры" icon="/storage/ui/icons/Viewing.png" dense>
        <div class="grid gap-4 lg:grid-cols-[1fr_16rem]">
            <ParchmentPanel>
                <p class="gm-kicker">Запись мастера</p>
                <h3 class="mt-2 text-2xl font-semibold text-[#fff1c8]">{{ game.name }}</h3>
                <p v-if="game.description" class="mt-3 text-sm leading-7 text-[#d8c6a4]">{{ game.description }}</p>
                <p v-else class="mt-3 text-sm leading-7 text-[#9d8b6d]">Описание игры пока не заполнено.</p>
            </ParchmentPanel>

            <div class="space-y-3">
                <div class="gm-overview-row">
                    <span>Роль</span>
                    <strong>{{ game.current_user_role_label }}</strong>
                </div>
                <div class="gm-overview-row">
                    <span>Владелец</span>
                    <strong>{{ game.owner.name }}</strong>
                </div>
                <div class="gm-overview-row">
                    <span>Участники</span>
                    <strong>{{ game.members.length }}</strong>
                </div>
            </div>
        </div>
    </FantasyPanel>
</template>
