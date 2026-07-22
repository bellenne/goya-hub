<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FantasyBadge from '@/Components/Fantasy/FantasyBadge.vue';
import FantasyButton from '@/Components/Fantasy/FantasyButton.vue';
import GameMasterLayout from '@/Components/GameMaster/GameMasterLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    game: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const inviteForm = useForm({});
const copiedInvite = ref(false);

const dashboard = computed(() => props.game.dashboard ?? {});
const summary = computed(() => dashboard.value.summary ?? {});
const isModernTheme = computed(() => (page.props.auth?.user?.theme_preference ?? 'classic') === 'modern');
const activeInviteLink = computed(() => page.props.flash.invite_link || props.game.invite_link);
const playerCount = computed(() => props.game.members.filter((member) => member.role === 'player').length);
const featuredSession = computed(() => dashboard.value.featured_session ?? null);
const featuredNpc = computed(() => dashboard.value.featured_npc ?? null);
const selectedCharacter = computed(() => dashboard.value.selected_character ?? null);
const featuredBackground = computed(() => dashboard.value.featured_background ?? null);
const selectedNpcId = ref(null);
const imageFrames = ref({});
const npcChoices = computed(() => dashboard.value.recent_npcs ?? []);
const selectedNpc = computed(() => {
    return npcChoices.value.find((npc) => npc.id === selectedNpcId.value)
        ?? featuredNpc.value
        ?? npcChoices.value[0]
        ?? null;
});

const sectionItems = computed(() => {
    const items = [
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
            available: props.game.can_view_sessions,
        },
        {
            label: 'Персонажи',
            description: 'Герои партии',
            href: route('games.characters.index', props.game.id),
            active: route().current('games.characters.*'),
            icon: '/storage/ui/icons/Characters.png',
            available: props.game.can_view_characters,
        },
        {
            label: 'НПС',
            description: 'Персонажи мира',
            href: route('games.npcs.index', props.game.id),
            active: route().current('games.npcs.*'),
            icon: '/storage/ui/icons/players.png',
            available: props.game.can_manage_content,
        },
        {
            label: 'Фоны',
            description: 'Локации и истории',
            href: route('games.backgrounds.index', props.game.id),
            active: route().current('games.backgrounds.*'),
            icon: '/storage/ui/icons/backgrounds.png',
            available: props.game.can_manage_content,
        },
        {
            label: 'Предметы',
            description: 'Снаряжение и вещи',
            href: route('games.items.index', props.game.id),
            active: route().current('games.items.*'),
            icon: '/storage/ui/icons/inventory.png',
            available: props.game.can_manage_content,
        },
        {
            label: 'Заметки',
            description: 'Записи мастера',
            href: route('games.notes.index', props.game.id),
            active: route().current('games.notes.*'),
            icon: '/storage/ui/icons/Notes.png',
            available: props.game.can_manage_content,
        },
        {
            label: 'Лист персонажа',
            description: 'Шаблон и поля',
            href: route('games.character-template.edit', props.game.id),
            active: route().current('games.character-template.*'),
            icon: '/storage/ui/icons/Settings.png',
            available: props.game.can_manage_content,
        },
        {
            label: 'Тикеты',
            description: 'Поддержка и запросы',
            href: route('games.tickets.index', props.game.id),
            active: route().current('games.tickets.*'),
            icon: '/storage/ui/icons/Tickets.png',
            available: props.game.can_view_tickets,
        },
        {
            label: props.game.current_user_character_id ? 'Мой персонаж' : 'Создать персонажа',
            description: props.game.current_user_character_id
                ? 'Личный лист'
                : 'Новый лист',
            href: route('games.character.edit', props.game.id),
            active: route().current('games.character.edit'),
            icon: '/storage/ui/icons/Edit.png',
            available: props.game.can_edit_character,
        },
    ];

    return items.filter((item) => item.available);
});

const backgroundStyle = computed(() => {
    if (!featuredBackground.value?.image_url) {
        return {};
    }

    return {
        backgroundImage: `linear-gradient(90deg, rgba(13, 7, 4, 0.95), rgba(13, 7, 4, 0.56), rgba(13, 7, 4, 0.9)), url('/storage/ui/ui_background.png'), url('${featuredBackground.value.image_url}')`,
        backgroundSize: 'auto, 560px auto, cover',
        backgroundPosition: 'center, center, center',
    };
});

const formatDateTime = (value) => {
    if (!value) {
        return 'Не задано';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const statusLabel = (session) => session?.status_label ?? 'Не запланирована';
const ticketToneClass = (tone) => ({
    sky: 'gm-ticket-sky',
    amber: 'gm-ticket-amber',
    violet: 'gm-ticket-violet',
    emerald: 'gm-ticket-emerald',
    stone: 'gm-ticket-stone',
}[tone] ?? 'gm-ticket-sky');

const submitInvite = () => {
    inviteForm.post(route('games.invites.store', props.game.id), {
        preserveScroll: true,
        preserveState: true,
    });
};

const copyInvite = async () => {
    if (!activeInviteLink.value || !navigator.clipboard) {
        return;
    }

    await navigator.clipboard.writeText(activeInviteLink.value);
    copiedInvite.value = true;
    window.setTimeout(() => {
        copiedInvite.value = false;
    }, 1600);
};

const selectNpc = (npc) => {
    selectedNpcId.value = npc.id;
};

const imageFrameKey = (scope, id, url) => `${scope}:${id ?? url ?? 'empty'}`;

const registerImageFrame = (key, event) => {
    const image = event.target;

    if (!image?.naturalWidth || !image?.naturalHeight) {
        return;
    }

    const longestSide = Math.max(image.naturalWidth, image.naturalHeight);

    imageFrames.value = {
        ...imageFrames.value,
        [key]: {
            '--gm-image-ratio-w': (image.naturalWidth / longestSide).toFixed(5),
            '--gm-image-ratio-h': (image.naturalHeight / longestSide).toFixed(5),
        },
    };
};

const imageFrameStyle = (key) => imageFrames.value[key] ?? {};
</script>

<template>
    <Head :title="game.name" />

    <AuthenticatedLayout v-if="isModernTheme">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">GM Dashboard</p>
                    <h1 class="mt-2 text-3xl font-semibold text-white">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
                        Современный режим панели мастера: сессии, тикеты, контент и состав игры.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.index')">
                        <SecondaryButton>К играм</SecondaryButton>
                    </Link>
                    <Link v-if="game.can_view_sessions" :href="route('games.sessions.index', game.id)">
                        <PrimaryButton>Сессии</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <div v-if="page.props.flash.success" class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                    {{ page.props.flash.success }}
                </div>

                <section class="theme-panel">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="theme-list-row">
                            <p class="text-sm text-slate-400">Персонажи</p>
                            <strong class="mt-2 block text-3xl text-white">{{ summary.characters ?? 0 }}</strong>
                        </div>
                        <div class="theme-list-row">
                            <p class="text-sm text-slate-400">Сессии</p>
                            <strong class="mt-2 block text-3xl text-white">{{ summary.sessions ?? 0 }}</strong>
                        </div>
                        <div class="theme-list-row">
                            <p class="text-sm text-slate-400">Активные тикеты</p>
                            <strong class="mt-2 block text-3xl text-white">{{ summary.active_tickets ?? 0 }}</strong>
                        </div>
                        <div class="theme-list-row">
                            <p class="text-sm text-slate-400">Материалы</p>
                            <strong class="mt-2 block text-3xl text-white">{{ (summary.npcs ?? 0) + (summary.items ?? 0) + (summary.backgrounds ?? 0) }}</strong>
                        </div>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <section class="theme-panel">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200/70">Ближайшая сессия</p>
                                <h2 class="mt-2 text-2xl font-semibold text-white">{{ featuredSession?.title ?? 'Сессия не создана' }}</h2>
                                <p class="mt-2 text-sm text-slate-400">{{ statusLabel(featuredSession) }} · {{ formatDateTime(featuredSession?.started_at) }}</p>
                            </div>
                            <Link v-if="featuredSession && game.can_view_sessions" :href="route('games.sessions.show', [game.id, featuredSession.id])">
                                <SecondaryButton>Открыть</SecondaryButton>
                            </Link>
                        </div>
                    </section>

                    <section class="theme-panel">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200/70">Приглашение</p>
                        <div class="mt-4 flex flex-col gap-3">
                            <form v-if="game.can_manage_invites" @submit.prevent="submitInvite">
                                <PrimaryButton :disabled="inviteForm.processing">
                                    {{ activeInviteLink ? 'Обновить ссылку' : 'Создать ссылку' }}
                                </PrimaryButton>
                            </form>
                            <div v-if="activeInviteLink" class="flex flex-col gap-3 sm:flex-row">
                                <input :value="activeInviteLink" class="w-full rounded-2xl border border-white/10 bg-stone-900 px-4 py-3 text-sm text-slate-200" readonly />
                                <SecondaryButton @click="copyInvite">{{ copiedInvite ? 'Скопировано' : 'Копировать' }}</SecondaryButton>
                            </div>
                            <p v-else class="text-sm text-slate-500">Активной ссылки пока нет.</p>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>

    <GameMasterLayout
        v-else
        :game="game"
        :navigation="sectionItems"
        title="Панель Мастера"
        subtitle="Управление игрой и миром"
    >
        <div class="gm-dashboard">
            <div v-if="page.props.flash.success" class="gm-alert gm-alert-success">
                {{ page.props.flash.success }}
            </div>

            <div class="gm-dashboard-grid">
                <section class="gm-panel gm-panel-dense gm-session-panel gm-dashboard-session" :style="backgroundStyle">
                    <div class="gm-panel-head">
                        <div class="flex items-center gap-3">
                            <span class="gm-panel-icon">
                                <img src="/storage/ui/icons/Sessions.png" alt="" />
                            </span>
                            <div>
                                <p class="gm-kicker">Ближайшая сессия</p>
                                <h3 class="gm-panel-title">{{ featuredSession?.title ?? 'Сессия не создана' }}</h3>
                            </div>
                        </div>
                        <FantasyBadge>{{ statusLabel(featuredSession) }}</FantasyBadge>
                    </div>

                    <div class="relative z-10 mt-5 grid min-h-[13rem] gap-4 md:grid-cols-[1fr_auto] md:items-end">
                        <div class="space-y-3">
                            <div class="gm-session-meta">
                                <span>Время</span>
                                <strong>{{ formatDateTime(featuredSession?.started_at) }}</strong>
                            </div>
                            <div class="gm-session-meta">
                                <span>Участники</span>
                                <strong>{{ featuredSession?.participants_count ?? game.members.length }}</strong>
                            </div>
                            <div v-if="featuredBackground" class="gm-session-meta">
                                <span>Фон сцены</span>
                                <strong>{{ featuredBackground.title }}</strong>
                            </div>
                        </div>

                        <Link v-if="featuredSession && game.can_view_sessions" :href="route('games.sessions.show', [game.id, featuredSession.id])">
                            <FantasyButton>Открыть сессию</FantasyButton>
                        </Link>
                        <Link v-else-if="game.can_manage_sessions" :href="route('games.sessions.index', game.id)">
                            <FantasyButton>Создать сессию</FantasyButton>
                        </Link>
                    </div>
                </section>

                <section class="gm-panel gm-panel-dense gm-dashboard-tickets">
                    <div class="gm-panel-head">
                        <div class="flex items-center gap-3">
                            <span class="gm-panel-icon">
                                <img src="/storage/ui/icons/Tickets.png" alt="" />
                            </span>
                            <div>
                                <p class="gm-kicker">Активные тикеты</p>
                                <h3 class="gm-panel-title">{{ summary.active_tickets ?? 0 }} открыто</h3>
                            </div>
                        </div>
                    </div>

                    <div v-if="dashboard.active_tickets?.length" class="relative z-10 mt-4 space-y-3">
                        <Link
                            v-for="ticket in dashboard.active_tickets"
                            :key="ticket.id"
                            :href="route('games.tickets.show', [game.id, ticket.id])"
                            class="gm-ticket-row"
                            :class="ticketToneClass(ticket.status_tone)"
                        >
                            <span class="gm-ticket-dot" />
                            <span class="min-w-0 flex-1">
                                <strong>{{ ticket.title }}</strong>
                                <small>Игрок: {{ ticket.creator_name ?? 'не указан' }}</small>
                            </span>
                            <span>{{ formatDateTime(ticket.last_message_at || ticket.updated_at) }}</span>
                        </Link>
                    </div>
                    <div v-else class="gm-empty mt-4">Активных тикетов нет.</div>

                    <div class="gm-panel-actions mt-4">
                        <Link v-if="game.can_view_tickets" :href="route('games.tickets.index', game.id)">
                            <FantasyButton variant="secondary">Все тикеты</FantasyButton>
                        </Link>
                    </div>
                </section>

                <section class="gm-panel gm-panel-dense gm-dashboard-npc">
                    <div class="gm-panel-head">
                        <div class="flex items-center gap-3">
                            <span class="gm-panel-icon">
                                <img src="/storage/ui/icons/players.png" alt="" />
                            </span>
                            <div>
                                <p class="gm-kicker">Выбранный НПС</p>
                                <h3 class="gm-panel-title">{{ selectedNpc?.name ?? 'НПС не выбран' }}</h3>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedNpc" class="relative z-10 mt-4 grid gap-4 sm:grid-cols-[8rem_1fr]">
                        <div
                            class="gm-portrait gm-image-frame gm-image-frame-npc"
                            :style="imageFrameStyle(imageFrameKey('npc', selectedNpc.id, selectedNpc.avatar_url))"
                        >
                            <img
                                v-if="selectedNpc.avatar_url"
                                :src="selectedNpc.avatar_url"
                                alt=""
                                @load="registerImageFrame(imageFrameKey('npc', selectedNpc.id, selectedNpc.avatar_url), $event)"
                            />
                            <img v-else src="/storage/ui/icons/players.png" alt="" class="gm-portrait-icon" />
                        </div>
                        <div class="min-w-0 space-y-3">
                            <FantasyBadge>{{ selectedNpc.type_label }}</FantasyBadge>
                            <p class="line-clamp-5 text-sm leading-6 text-[#d7c5a4]">
                                {{ selectedNpc.description || 'Описание НПС пока не заполнено.' }}
                            </p>
                            <Link :href="route('games.npcs.edit', [game.id, selectedNpc.id])">
                                <FantasyButton variant="secondary">Открыть НПС</FantasyButton>
                            </Link>
                        </div>
                    </div>
                    <div v-if="selectedNpc && npcChoices.length > 1" class="gm-npc-picker">
                        <button
                            v-for="npc in npcChoices"
                            :key="npc.id"
                            type="button"
                            class="gm-npc-choice gm-image-frame gm-image-frame-npc-choice"
                            :class="{ 'gm-npc-choice-active': selectedNpc?.id === npc.id }"
                            :aria-label="`Выбрать НПС ${npc.name}`"
                            :style="imageFrameStyle(imageFrameKey('npc-choice', npc.id, npc.avatar_url))"
                            @click="selectNpc(npc)"
                        >
                            <img
                                v-if="npc.avatar_url"
                                :src="npc.avatar_url"
                                alt=""
                                @load="registerImageFrame(imageFrameKey('npc-choice', npc.id, npc.avatar_url), $event)"
                            />
                            <img v-else src="/storage/ui/icons/players.png" alt="" />
                        </button>
                    </div>
                    <div v-else-if="!selectedNpc" class="gm-empty mt-4">Каталог НПС пока пуст.</div>
                </section>

                <section id="party" class="gm-panel gm-panel-dense gm-party-panel gm-dashboard-party">
                    <div class="gm-panel-head">
                        <div class="flex items-center gap-3">
                            <span class="gm-panel-icon">
                                <img src="/storage/ui/icons/Characters.png" alt="" />
                            </span>
                            <div>
                                <p class="gm-kicker">Текущая партия</p>
                                <h3 class="gm-panel-title">{{ summary.characters ?? 0 }} персонажей</h3>
                            </div>
                        </div>
                        <FantasyBadge tone="muted">{{ game.members.length }} участников</FantasyBadge>
                    </div>

                    <div v-if="dashboard.party?.length" class="relative z-10 mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
                        <Link
                            v-for="character in dashboard.party"
                            :key="character.id"
                            :href="route('games.characters.show', [game.id, character.id])"
                            class="gm-party-card"
                        >
                            <span
                                class="gm-party-avatar gm-image-frame gm-image-frame-party"
                                :style="imageFrameStyle(imageFrameKey('party', character.id, character.avatar_url))"
                            >
                                <img
                                    v-if="character.avatar_url"
                                    :src="character.avatar_url"
                                    alt=""
                                    @load="registerImageFrame(imageFrameKey('party', character.id, character.avatar_url), $event)"
                                />
                                <img v-else src="/storage/ui/icons/Profile.png" alt="" />
                            </span>
                            <span class="gm-party-card-body min-w-0">
                                <strong>{{ character.name }}</strong>
                                <small>{{ character.origin || character.user?.name || 'Герой партии' }}</small>
                            </span>
                        </Link>
                    </div>
                    <div v-else class="gm-empty mt-4">Персонажи партии пока не созданы.</div>
                </section>

                <section class="gm-panel gm-panel-dense gm-items-panel gm-dashboard-items">
                    <div class="gm-panel-head">
                        <div class="flex items-center gap-3">
                            <span class="gm-panel-icon">
                                <img src="/storage/ui/icons/inventory.png" alt="" />
                            </span>
                            <div>
                                <p class="gm-kicker">Недавние предметы</p>
                                <h3 class="gm-panel-title">{{ summary.items ?? 0 }} в каталоге</h3>
                            </div>
                        </div>
                    </div>

                    <div v-if="dashboard.recent_items?.length" class="relative z-10 mt-4 space-y-2">
                        <Link
                            v-for="item in dashboard.recent_items"
                            :key="item.id"
                            :href="route('games.items.edit', [game.id, item.id])"
                            class="gm-item-row"
                        >
                            <span
                                class="gm-item-thumb gm-image-frame gm-image-frame-item"
                                :style="imageFrameStyle(imageFrameKey('item', item.id, item.image_url))"
                            >
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    alt=""
                                    @load="registerImageFrame(imageFrameKey('item', item.id, item.image_url), $event)"
                                />
                                <img v-else src="/storage/ui/icons/inventory.png" alt="" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <strong>{{ item.name }}</strong>
                                <small>{{ item.category || 'Без категории' }}</small>
                            </span>
                            <span>{{ formatDateTime(item.updated_at) }}</span>
                        </Link>
                    </div>
                    <div v-else class="gm-empty mt-4">Предметы пока не добавлены.</div>
                </section>
            </div>

            <section class="gm-character-sheet">
                <div v-if="selectedCharacter" class="relative z-10 grid gap-5 lg:grid-cols-[18rem_1fr]">
                    <div class="grid gap-4 sm:grid-cols-[9rem_1fr] lg:block">
                        <div
                            class="gm-sheet-portrait gm-image-frame gm-image-frame-sheet"
                            :style="imageFrameStyle(imageFrameKey('sheet', selectedCharacter.id, selectedCharacter.avatar_url))"
                        >
                            <img
                                v-if="selectedCharacter.avatar_url"
                                :src="selectedCharacter.avatar_url"
                                alt=""
                                @load="registerImageFrame(imageFrameKey('sheet', selectedCharacter.id, selectedCharacter.avatar_url), $event)"
                            />
                            <img v-else src="/storage/ui/icons/Profile.png" alt="" class="gm-sheet-icon" />
                        </div>
                        <div class="min-w-0 lg:mt-4">
                            <p class="gm-sheet-kicker">Просмотр листа персонажа</p>
                            <h3 class="gm-sheet-title">{{ selectedCharacter.name }}</h3>
                            <p class="mt-2 text-sm leading-6 text-[#4a2a12]">
                                {{ selectedCharacter.origin || selectedCharacter.user?.name || 'Персонаж партии' }}
                            </p>
                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                                <div class="gm-sheet-stat">
                                    <span>Статус</span>
                                    <strong>{{ selectedCharacter.is_active ? 'Активен' : 'В архиве' }}</strong>
                                </div>
                                <div class="gm-sheet-stat">
                                    <span>Навыки</span>
                                    <strong>{{ selectedCharacter.skills?.length ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-[1fr_0.9fr]">
                        <div>
                            <p class="gm-sheet-section-title">Способности</p>
                            <div v-if="selectedCharacter.attributes?.length" class="mt-3 grid gap-x-5 gap-y-2 sm:grid-cols-2">
                                <div v-for="attribute in selectedCharacter.attributes" :key="attribute.key" class="gm-sheet-line">
                                    <span>{{ attribute.label }}</span>
                                    <strong>{{ attribute.value }}</strong>
                                </div>
                            </div>
                            <div v-else class="mt-3 text-sm text-[#6a4727]">Шаблон характеристик пока не настроен.</div>
                        </div>

                        <div>
                            <p class="gm-sheet-section-title">Навыки и поля</p>
                            <div v-if="selectedCharacter.skills?.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="skill in selectedCharacter.skills" :key="skill.key" class="gm-sheet-chip">
                                    {{ skill.label }}
                                </span>
                            </div>
                            <div v-else class="mt-3 text-sm text-[#6a4727]">Активные навыки не отмечены.</div>

                            <div v-if="selectedCharacter.extra_fields?.length" class="mt-5 space-y-2">
                                <div v-for="field in selectedCharacter.extra_fields" :key="field.key" class="gm-sheet-line">
                                    <span>{{ field.label }}</span>
                                    <strong>{{ field.value || '—' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 flex justify-center">
                        <Link :href="route('games.characters.show', [game.id, selectedCharacter.id])">
                            <FantasyButton>Открыть полный лист</FantasyButton>
                        </Link>
                    </div>
                </div>
                <div v-else class="relative z-10 text-[#4a2a12]">
                    Персонажи пока не созданы.
                </div>
            </section>

            <footer class="gm-bottom-bar">
                <span>Мир: {{ game.name }}</span>
                <span>Игроков: {{ playerCount }}</span>
                <span>{{ featuredSession ? `Сессия: ${statusLabel(featuredSession)}` : 'Сессия не активна' }}</span>
                <Link :href="route('profile.edit')" class="gm-bottom-link">
                    <img src="/storage/ui/icons/Settings.png" alt="" />
                    Настройки пользователя
                </Link>
            </footer>
        </div>
    </GameMasterLayout>
</template>
