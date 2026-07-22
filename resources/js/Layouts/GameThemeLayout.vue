<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GameMasterLayout from '@/Components/GameMaster/GameMasterLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ThemeIcon from '@/Components/ThemeIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    game: {
        type: Object,
        required: true,
    },
    section: {
        type: String,
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
    backHref: {
        type: String,
        default: '',
    },
    backLabel: {
        type: String,
        default: 'К панели',
    },
    actionHref: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    immersive: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const isModernTheme = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern');
const gameAccess = computed(() => {
    const access = page.props.game_access;

    return access?.id === props.game.id
        ? { ...props.game, ...access }
        : props.game;
});

const can = (key) => gameAccess.value?.[key] === true;

const navigation = computed(() => [
    {
        label: 'Панель',
        description: 'Обзор',
        href: route('games.show', props.game.id),
        active: route().current('games.show'),
        icon: '/storage/ui/icons/Viewing.png',
        available: true,
    },
    {
        label: 'Сессии',
        description: 'Планирование',
        href: route('games.sessions.index', props.game.id),
        active: route().current('games.sessions.*'),
        icon: '/storage/ui/icons/Sessions.png',
        available: can('can_view_sessions'),
    },
    {
        label: 'Персонажи',
        description: 'Герои партии',
        href: route('games.characters.index', props.game.id),
        active: route().current('games.characters.*'),
        icon: '/storage/ui/icons/Characters.png',
        available: can('can_view_characters'),
    },
    {
        label: 'НПС',
        description: 'Персонажи мира',
        href: route('games.npcs.index', props.game.id),
        active: route().current('games.npcs.*'),
        icon: '/storage/ui/icons/players.png',
        available: can('can_manage_content'),
    },
    {
        label: 'Фоны',
        description: 'Локации и истории',
        href: route('games.backgrounds.index', props.game.id),
        active: route().current('games.backgrounds.*'),
        icon: '/storage/ui/icons/backgrounds.png',
        available: can('can_manage_content'),
    },
    {
        label: 'Предметы',
        description: 'Снаряжение и вещи',
        href: route('games.items.index', props.game.id),
        active: route().current('games.items.*'),
        icon: '/storage/ui/icons/inventory.png',
        available: can('can_manage_content'),
    },
    {
        label: 'Заметки',
        description: 'Записи мастера',
        href: route('games.notes.index', props.game.id),
        active: route().current('games.notes.*'),
        icon: '/storage/ui/icons/Notes.png',
        available: can('can_manage_content'),
    },
    {
        label: 'Лист персонажа',
        description: 'Шаблон и поля',
        href: route('games.character-template.edit', props.game.id),
        active: route().current('games.character-template.*'),
        icon: '/storage/ui/icons/Settings.png',
        available: can('can_manage_content'),
    },
    {
        label: 'Тикеты',
        description: 'Поддержка и запросы',
        href: route('games.tickets.index', props.game.id),
        active: route().current('games.tickets.*'),
        icon: '/storage/ui/icons/Tickets.png',
        available: can('can_view_tickets'),
    },
    {
        label: gameAccess.value.current_user_character_id ? 'Мой персонаж' : 'Создать персонажа',
        description: gameAccess.value.current_user_character_id ? 'Личный лист' : 'Новый лист',
        href: route('games.character.edit', props.game.id),
        active: route().current('games.character.edit'),
        icon: '/storage/ui/icons/Edit.png',
        available: can('can_edit_character'),
    },
].filter((item) => item.available));
</script>

<template>
    <slot v-if="immersive" />

    <AuthenticatedLayout v-else-if="isModernTheme">
        <template #header>
            <slot name="header">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="fantasy-kicker">{{ section }}</p>
                        <h1 class="fantasy-title text-3xl">{{ title }}</h1>
                        <p v-if="subtitle" class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                            {{ subtitle }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link v-if="backHref" :href="backHref">
                            <SecondaryButton>{{ backLabel }}</SecondaryButton>
                        </Link>
                        <Link v-if="actionHref && actionLabel" :href="actionHref">
                            <PrimaryButton>{{ actionLabel }}</PrimaryButton>
                        </Link>
                        <slot name="actions" />
                    </div>
                </div>
            </slot>
        </template>

        <nav class="app-game-navigation" aria-label="Навигация по игре">
            <Link
                v-for="item in navigation"
                :key="item.label"
                :href="item.href"
                class="app-game-navigation-link"
                :class="{ 'app-game-navigation-link-active': item.active }"
            >
                <span class="app-game-navigation-icon">
                    <ThemeIcon :src="item.icon" />
                </span>
                <span>{{ item.label }}</span>
            </Link>
        </nav>

        <slot />
    </AuthenticatedLayout>

    <GameMasterLayout
        v-else
        :game="gameAccess"
        :navigation="navigation"
        :title="section"
        :subtitle="title"
    >
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </GameMasterLayout>
</template>
