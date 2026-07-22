<script setup>
import GameThemeLayout from '@/Layouts/GameThemeLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    game: { type: Object, required: true },
    sessions: { type: Array, required: true },
    can_manage_sessions: { type: Boolean, required: true },
});

const page = usePage();
const showCreateModal = ref(false);
const showJoinModal = ref(false);
const createForm = useForm({ title: '' });
const joinForm = useForm({ invite_code: '' });

const activeSessionsCount = computed(() => props.sessions.filter((session) => ['active', 'gm_disconnected_grace'].includes(session.status)).length);
const endedSessionsCount = computed(() => props.sessions.filter((session) => session.status === 'ended').length);
const totalParticipantsCount = computed(() => props.sessions.reduce((total, session) => total + session.participants_count, 0));

const formatDateTime = (value) => (value ? new Date(value).toLocaleString() : '—');
const formatDuration = (seconds) => {
    if (seconds === null || seconds === undefined) {
        return '—';
    }

    const total = Number(seconds);
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const secs = total % 60;

    return [
        hours ? `${hours} ч` : null,
        minutes ? `${minutes} мин` : null,
        !hours && !minutes ? `${secs} сек` : null,
    ].filter(Boolean).join(' ');
};

const submitCreate = () => {
    createForm.post(route('games.sessions.store', props.game.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            createForm.reset();
            showCreateModal.value = false;
        },
    });
};

const submitJoinByCode = () => {
    joinForm.post(route('games.sessions.join-by-code', props.game.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            joinForm.reset();
            showJoinModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Сессии - ${game.name}`" />

    <GameThemeLayout :game="game" section="Сессии" :title="game.name">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Сессии</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Лобби и активные игровые столы для этой кампании.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Назад к игре</SecondaryButton>
                    </Link>
                    <SecondaryButton @click="showJoinModal = true">Войти по коду</SecondaryButton>
                    <PrimaryButton v-if="can_manage_sessions" @click="showCreateModal = true">Создать сессию</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="fantasy-kicker">Список сессий</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Лобби и активные сцены</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                                Откройте нужную сессию, чтобы перейти в лобби или на активный игровой стол.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="gm-badge">{{ sessions.length }} всего</span>
                            <span class="gm-badge">{{ activeSessionsCount }} активных</span>
                            <span class="gm-badge">{{ totalParticipantsCount }} участников</span>
                            <span class="gm-badge">{{ endedSessionsCount }} завершено</span>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <SecondaryButton @click="showJoinModal = true">Войти по коду</SecondaryButton>
                            <PrimaryButton v-if="can_manage_sessions" @click="showCreateModal = true">Создать сессию</PrimaryButton>
                        </div>
                    </div>

                    <div v-if="page.props.flash.success" class="gm-alert gm-alert-success mt-5">
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="sessions.length === 0" class="mt-5 theme-empty">
                        Для этой игры пока нет созданных сессий.
                    </div>

                    <div v-else class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="session in sessions"
                            :key="session.id"
                            class="theme-card theme-card-interactive group"
                            :class="session.status === 'ended' ? 'opacity-75 hover:translate-y-0 hover:border-stone-700/50' : ''"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-200/70">Сессия</p>
                                    <h3 class="mt-3 text-xl font-semibold text-amber-50">{{ session.title }}</h3>
                                </div>
                                <div
                                    class="gm-badge px-3 py-1 text-[11px]"
                                    :class="session.status === 'ended'
                                        ? 'gm-badge-danger'
                                        : session.status === 'active'
                                        ? 'gm-badge-success'
                                        : session.status === 'gm_disconnected_grace'
                                        ? 'gm-badge-warning'
                                        : 'gm-badge-muted'"
                                >
                                    {{ session.status_label }}
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3">
                                <div class="theme-list-row">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Код</div>
                                    <div class="mt-2 text-sm font-medium uppercase tracking-[0.2em] text-stone-100">{{ session.invite_code }}</div>
                                </div>
                                <div class="theme-list-row">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Участников</div>
                                    <div class="mt-2 text-sm font-medium text-stone-100">{{ session.participants_count }}</div>
                                </div>
                            </div>

                            <div v-if="session.status === 'ended'" class="theme-list-row mt-4 grid gap-3 text-sm">
                                <div class="flex justify-between gap-3">
                                    <span class="text-stone-500">Старт</span>
                                    <span class="text-stone-200">{{ formatDateTime(session.started_at) }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-stone-500">Завершение</span>
                                    <span class="text-stone-200">{{ formatDateTime(session.ended_at) }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-stone-500">Длительность</span>
                                    <span class="text-stone-200">{{ formatDuration(session.duration_seconds) }}</span>
                                </div>
                            </div>

                            <div class="mt-4 theme-list-row">
                                <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Ссылка приглашения</div>
                                <input :value="session.invite_link" readonly class="mt-2 block w-full bg-transparent text-sm text-stone-300 outline-none" />
                            </div>

                            <Link v-if="session.is_openable" :href="route('games.sessions.show', [game.id, session.id])" class="mt-5 block">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-stone-500 transition group-hover:text-stone-300">Открыть сессию</span>
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-amber-200/25 bg-amber-400/10 text-lg text-amber-100 transition group-hover:translate-x-1">
                                        →
                                    </span>
                                </div>
                            </Link>
                            <div v-else class="theme-empty mt-5 text-red-100">
                                Завершённую сессию нельзя открыть повторно.
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Создать сессию</h2>
                <p class="mt-2 text-sm leading-6 text-stone-400">
                    Новая сессия появится в списке как лобби и будет готова к подключению участников.
                </p>
                <form class="mt-6 space-y-4" @submit.prevent="submitCreate">
                    <div>
                        <InputLabel for="session-title" value="Название сессии" />
                        <TextInput id="session-title" v-model="createForm.title" class="mt-2 block w-full" />
                        <InputError class="mt-2" :message="createForm.errors.title" />
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton @click="showCreateModal = false">Отмена</SecondaryButton>
                        <PrimaryButton :disabled="createForm.processing">Создать сессию</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showJoinModal" @close="showJoinModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Войти по коду</h2>
                <p class="mt-2 text-sm leading-6 text-stone-400">
                    Введите шестизначный код сессии, чтобы сразу подключиться к её лобби.
                </p>
                <form class="mt-6 space-y-4" @submit.prevent="submitJoinByCode">
                    <div>
                        <InputLabel for="invite-code" value="Код сессии" />
                        <TextInput id="invite-code" v-model="joinForm.invite_code" class="mt-2 block w-full uppercase" maxlength="6" />
                        <InputError class="mt-2" :message="joinForm.errors.invite_code" />
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton @click="showJoinModal = false">Отмена</SecondaryButton>
                        <PrimaryButton :disabled="joinForm.processing">Войти в лобби</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </GameThemeLayout>
</template>
