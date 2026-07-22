<script setup>
import FantasyButton from '@/Components/Fantasy/FantasyButton.vue';
import FantasyPanel from '@/Components/Fantasy/FantasyPanel.vue';
import ParchmentPanel from '@/Components/Fantasy/ParchmentPanel.vue';

defineProps({
    activeInviteLink: {
        type: String,
        default: '',
    },
    canManageInvites: {
        type: Boolean,
        default: false,
    },
    processing: {
        type: Boolean,
        default: false,
    },
    copied: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['generate', 'copy']);
</script>

<template>
    <FantasyPanel title="Приглашение" kicker="Записка мастера" icon="/storage/ui/icons/Tickets.png" dense>
        <p class="text-sm leading-6 text-[#b8a685]">
            Для игры может существовать одна активная ссылка приглашения. Сгенерируйте новую, если старую нужно заменить.
        </p>

        <form v-if="canManageInvites" class="mt-5" @submit.prevent="$emit('generate')">
            <FantasyButton type="submit" :disabled="processing">
                {{ activeInviteLink ? 'Перегенерировать ссылку' : 'Сгенерировать ссылку' }}
            </FantasyButton>
        </form>

        <ParchmentPanel v-if="activeInviteLink" class="mt-5">
            <p class="gm-kicker">Активная ссылка</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <input :value="activeInviteLink" class="gm-input w-full" readonly />
                <FantasyButton variant="secondary" @click="$emit('copy')">
                    {{ copied ? 'Скопировано' : 'Копировать' }}
                </FantasyButton>
            </div>
        </ParchmentPanel>

        <div v-else class="gm-empty mt-5">
            Активной ссылки пока нет.
        </div>

        <div v-if="!canManageInvites" class="gm-empty mt-5">
            Управлять приглашениями могут только мастера игры.
        </div>
    </FantasyPanel>
</template>
