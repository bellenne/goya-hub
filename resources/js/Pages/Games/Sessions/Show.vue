<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
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
let presenceChannel = null;

const participantIds = computed(() => props.session.participants.map((participant) => participant.user.id));
const isJoined = computed(() => participantIds.value.includes(page.props.auth.user.id));

const refreshLobby = () => {
    router.reload({ only: ['session', 'can_manage_sessions'], preserveScroll: true });
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
        });
};

onMounted(() => {
    connectPresence();
});

onBeforeUnmount(() => {
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="fantasy-kicker">Lobby</p>
                    <h1 class="fantasy-title">{{ session.title }}</h1>
                </div>
                <Link :href="route('games.sessions.index', game.id)">
                    <SecondaryButton>К списку сессий</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash.success"
                    class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
                >
                    {{ page.props.flash.success }}
                </div>

                <section class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                    <div class="fantasy-panel">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="fantasy-chip">
                                {{ session.status_label }}
                            </div>
                            <div class="fantasy-chip-muted">
                                Code: {{ session.invite_code }}
                            </div>
                        </div>

                        <div class="mt-5">
                            <p class="text-sm text-stone-400">Invite link</p>
                            <input :value="session.invite_link" readonly class="fantasy-input mt-2 w-full" />
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <form v-if="!isJoined" @submit.prevent="submitJoin">
                                <PrimaryButton :disabled="joinForm.processing">Войти в лобби</PrimaryButton>
                            </form>
                            <form v-if="can_manage_sessions && session.status === 'lobby'" @submit.prevent="submitStart">
                                <PrimaryButton :disabled="startForm.processing">Начать игру</PrimaryButton>
                            </form>
                        </div>
                    </div>

                    <div class="fantasy-panel-muted">
                        <h2 class="text-lg font-semibold text-amber-50">Онлайн в лобби</h2>
                        <div v-if="onlineUsers.length === 0" class="mt-4 text-sm text-stone-400">
                            Никого онлайн.
                        </div>
                        <div v-else class="mt-4 space-y-3">
                            <div v-for="user in onlineUsers" :key="user.id" class="rounded-lg border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
                                {{ user.name }} · {{ user.email }}
                            </div>
                        </div>
                    </div>
                </section>

                <section class="fantasy-panel">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-amber-50">Участники сессии</h2>
                        <span class="text-sm text-stone-400">{{ session.participants.length }} total</span>
                    </div>

                    <div v-if="session.participants.length === 0" class="fantasy-empty mt-4">
                        Пока никто не присоединился к лобби.
                    </div>

                    <div v-else class="mt-4 grid gap-3 md:grid-cols-2">
                        <div v-for="participant in session.participants" :key="participant.id" class="fantasy-card flex items-center justify-between">
                            <div>
                                <p class="font-medium text-amber-50">{{ participant.user.name }}</p>
                                <p class="text-sm text-stone-400">{{ participant.user.email }}</p>
                            </div>
                            <div class="fantasy-chip-muted">
                                {{ onlineUsers.some((user) => user.id === participant.user.id) ? 'online' : 'offline' }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
