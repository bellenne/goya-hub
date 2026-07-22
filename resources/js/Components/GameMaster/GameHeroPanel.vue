<script setup>
import FantasyBadge from '@/Components/Fantasy/FantasyBadge.vue';

defineProps({
    game: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <section class="gm-hero">
        <div class="gm-hero-corner gm-hero-corner-left" />
        <div class="gm-hero-corner gm-hero-corner-right" />

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="gm-kicker">Игра</p>
                <h2 class="gm-hero-title">{{ game.name }}</h2>
                <p v-if="game.description" class="gm-hero-description">{{ game.description }}</p>
                <p v-else class="gm-hero-description gm-hero-description-muted">Описание игры пока не заполнено.</p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <FantasyBadge>{{ game.current_user_role_label }}</FantasyBadge>
                    <FantasyBadge tone="muted">Владелец: {{ game.owner.name }}</FantasyBadge>
                    <FantasyBadge tone="muted">{{ game.members.length }} участников</FantasyBadge>
                </div>
            </div>

            <div class="gm-hero-actions">
                <slot name="actions" />
            </div>
        </div>
    </section>
</template>
