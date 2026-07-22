<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useGmSessionPresence } from '@/Composables/useGmSessionPresence';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    session: { type: Object, required: true },
    can_manage_sessions: { type: Boolean, required: true },
});

const page = usePage();
const joinForm = useForm({});
const startForm = useForm({});
const onlineUsers = ref([]);
const lifecycleNotice = ref(props.session.status === 'gm_disconnected_grace'
    ? {
        tone: 'warning',
        title: 'GM вышел из session page',
        text: 'Сессия ещё жива. Если GM не вернётся в течение 5 минут, она завершится.',
        gm_grace_ends_at: props.session.gm_grace_ends_at,
    }
    : null);
let presenceChannel = null;
let lifecycleNoticeTimeout = null;

const participantIds = computed(() => props.session.participants.map((participant) => participant.user.id));
const isJoined = computed(() => participantIds.value.includes(page.props.auth.user.id));

const refreshLobby = () => {
    router.reload({ only: ['session', 'can_manage_sessions'], preserveScroll: true });
};

const handleLifecycle = (payload) => {
    if (lifecycleNoticeTimeout) {
        clearTimeout(lifecycleNoticeTimeout);
        lifecycleNoticeTimeout = null;
    }

    if (['connected', 'heartbeat'].includes(payload.event) && payload.status !== 'gm_disconnected_grace' && payload.status !== 'ended') {
        if (lifecycleNotice.value?.tone === 'warning') {
            lifecycleNotice.value = null;
        }

        return;
    }

    if (payload.event === 'gm_disconnected') {
        lifecycleNotice.value = {
            tone: 'warning',
            title: 'GM вышел из session page',
            text: 'Если GM не вернётся в течение 5 минут, сессия завершится.',
            gm_grace_ends_at: payload.gm_grace_ends_at,
        };
        refreshLobby();
        return;
    }

    if (payload.event === 'ended' || payload.status === 'ended') {
        lifecycleNotice.value = {
            tone: 'danger',
            title: 'Сессия завершена',
            text: 'GM не вернулся за 5 минут. Вы будете перенаправлены к списку сессий.',
        };
        setTimeout(() => router.visit(route('games.sessions.index', props.game.id)), 2500);
        return;
    }

    if (payload.event === 'gm_returned') {
        lifecycleNotice.value = {
            tone: 'success',
            title: 'GM вернулся',
            text: 'Сессия продолжается.',
        };
        lifecycleNoticeTimeout = setTimeout(() => {
            lifecycleNotice.value = null;
        }, 5000);
        refreshLobby();
        return;
    }
};

const connectPresence = () => {
    if (!window.Echo) {
        return;
    }

    presenceChannel = window.Echo.join(props.session.presence_channel)
        .here((users) => {
            onlineUsers.value = users;
        })
        .joining((user) => {
            onlineUsers.value = [...onlineUsers.value.filter((entry) => entry.id !== user.id), user];
        })
        .leaving((user) => {
            onlineUsers.value = onlineUsers.value.filter((entry) => entry.id !== user.id);
        })
        .listen('.session.lobby.updated', () => {
            refreshLobby();
        })
        .listen('.session.lifecycle.updated', handleLifecycle);
};

useGmSessionPresence({
    enabled: props.can_manage_sessions && props.session.status !== 'ended',
    gameId: props.game.id,
    sessionId: props.session.id,
    onStatus: handleLifecycle,
});

onMounted(() => {
    connectPresence();
});

onBeforeUnmount(() => {
    if (lifecycleNoticeTimeout) {
        clearTimeout(lifecycleNoticeTimeout);
    }

    if (window.Echo && presenceChannel) {
        window.Echo.leave(props.session.presence_channel);
    }
});

const submitJoin = () => {
    joinForm.post(route('games.sessions.join', [props.game.id, props.session.id]), {
        preserveScroll: true,
        preserveState: true,
    });
};

const submitStart = () => {
    startForm.post(route('games.sessions.start', [props.game.id, props.session.id]), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head :title="session.title" />

    <GameThemeLayout :game="game" section="Лобби" :title="session.title">
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="fantasy-kicker">Лобби</p>
                    <h1 class="fantasy-title">{{ session.title }}</h1>
                </div>
                <Link :href="route('games.sessions.index', game.id)">
                    <SecondaryButton>К списку сессий</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <div
                    v-if="lifecycleNotice"
                    class="gm-alert px-4 py-3 text-sm"
                    :class="{
                        'border-amber-400/40 bg-amber-400/10 text-amber-100': lifecycleNotice.tone === 'warning',
                        'border-emerald-400/40 bg-emerald-400/10 text-emerald-100': lifecycleNotice.tone === 'success',
                        'border-red-400/40 bg-red-400/10 text-red-100': lifecycleNotice.tone === 'danger',
                    }"
                >
                    <p class="font-semibold">{{ lifecycleNotice.title }}</p>
                    <p class="mt-1">{{ lifecycleNotice.text }}</p>
                    <p v-if="lifecycleNotice.gm_grace_ends_at" class="mt-1 text-xs opacity-80">
                        Grace до {{ new Date(lifecycleNotice.gm_grace_ends_at).toLocaleTimeString() }}
                    </p>
                </div>

                <div
                    v-if="page.props.flash.success"
                    class="gm-alert gm-alert-success"
                >
                    {{ page.props.flash.success }}
                </div>

                <section class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                    <div class="theme-panel">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="fantasy-chip">
                                {{ session.status_label }}
                            </div>
                            <div class="fantasy-chip-muted">
                                Код: {{ session.invite_code }}
                            </div>
                        </div>

                        <div class="mt-5">
                            <p class="text-sm text-stone-400">Ссылка приглашения</p>
                            <input :value="session.invite_link" readonly class="fantasy-input mt-2 w-full" />
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <form v-if="!isJoined" @submit.prevent="submitJoin">
                                <PrimaryButton :disabled="joinForm.processing || session.status === 'ended'">Войти в лобби</PrimaryButton>
                            </form>
                            <form v-if="can_manage_sessions && session.status === 'lobby'" @submit.prevent="submitStart">
                                <PrimaryButton :disabled="startForm.processing">Начать игру</PrimaryButton>
                            </form>
                        </div>
                    </div>

                    <div class="theme-panel">
                        <h2 class="text-lg font-semibold text-amber-50">Онлайн в лобби</h2>
                        <div v-if="onlineUsers.length === 0" class="mt-4 text-sm text-stone-400">
                            Никого онлайн.
                        </div>
                        <div v-else class="mt-4 space-y-3">
                            <div v-for="user in onlineUsers" :key="user.id" class="theme-list-row text-sm text-emerald-100">
                                {{ user.name }} · {{ user.email }}
                            </div>
                        </div>
                    </div>
                </section>

                <section class="theme-panel">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-amber-50">Участники сессии</h2>
                        <span class="gm-badge">{{ session.participants.length }} всего</span>
                    </div>

                    <div v-if="session.participants.length === 0" class="theme-empty mt-4">
                        Пока никто не присоединился к лобби.
                    </div>

                    <div v-else class="mt-4 grid gap-3 md:grid-cols-2">
                        <div v-for="participant in session.participants" :key="participant.id" class="theme-card flex items-center justify-between">
                            <div>
                                <p class="font-medium text-amber-50">{{ participant.user.name }}</p>
                                <p class="text-sm text-stone-400">{{ participant.user.email }}</p>
                            </div>
                            <div class="fantasy-chip-muted">
                                {{ onlineUsers.some((user) => user.id === participant.user.id) ? 'онлайн' : 'офлайн' }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </GameThemeLayout>
</template>
