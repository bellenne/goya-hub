<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

    <AuthenticatedLayout>
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

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-white/10 bg-stone-950/45 p-5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Игровые столы</p>
                                <h2 class="mt-3 text-3xl font-semibold text-amber-50">Список всех сессий</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">
                                    Создавайте новые лобби, запускайте активные сессии и подключайтесь по коду без лишних переходов.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Всего сессий</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ sessions.length }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Активные</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ activeSessionsCount }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Участники</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ totalParticipantsCount }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Завершены</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ endedSessionsCount }}</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Для мастера</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Новая сессия создаётся как лобби и может быть запущена в активный стол, когда партия готова.
                                </p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Для игрока</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Если у вас есть код сессии, можно сразу подключиться к нужному лобби через кнопку сверху.
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="fantasy-kicker">Список сессий</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Лобби и активные сцены</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                                Откройте нужную сессию, чтобы перейти в лобби или на активный игровой стол.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <SecondaryButton @click="showJoinModal = true">Войти по коду</SecondaryButton>
                            <PrimaryButton v-if="can_manage_sessions" @click="showCreateModal = true">Создать сессию</PrimaryButton>
                        </div>
                    </div>

                    <div v-if="page.props.flash.success" class="mt-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="sessions.length === 0" class="mt-5 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                        Для этой игры пока нет созданных сессий.
                    </div>

                    <div v-else class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="session in sessions"
                            :key="session.id"
                            class="group rounded-[1.5rem] border border-stone-700/50 bg-[radial-gradient(circle_at_top_left,rgba(45,212,191,0.12),transparent_12rem),rgba(18,18,16,0.92)] p-5 transition duration-300 hover:-translate-y-1 hover:border-amber-300/30 hover:shadow-[0_26px_60px_rgba(0,0,0,0.32)]"
                            :class="session.status === 'ended' ? 'opacity-75 hover:translate-y-0 hover:border-stone-700/50' : ''"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-200/70">Сессия</p>
                                    <h3 class="mt-3 text-xl font-semibold text-amber-50">{{ session.title }}</h3>
                                </div>
                                <div
                                    class="rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]"
                                    :class="session.status === 'ended'
                                        ? 'border-red-300/30 bg-red-400/10 text-red-100'
                                        : session.status === 'active'
                                        ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-100'
                                        : session.status === 'gm_disconnected_grace'
                                        ? 'border-amber-300/30 bg-amber-400/10 text-amber-100'
                                        : 'border-white/10 bg-white/[0.05] text-stone-300'"
                                >
                                    {{ session.status_label }}
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Код</div>
                                    <div class="mt-2 text-sm font-medium uppercase tracking-[0.2em] text-stone-100">{{ session.invite_code }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Участников</div>
                                    <div class="mt-2 text-sm font-medium text-stone-100">{{ session.participants_count }}</div>
                                </div>
                            </div>

                            <div v-if="session.status === 'ended'" class="mt-4 grid gap-3 rounded-2xl border border-red-300/15 bg-red-500/5 px-4 py-3 text-sm">
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

                            <div class="mt-4 rounded-2xl border border-white/10 bg-stone-950/70 px-4 py-3">
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
                            <div v-else class="mt-5 rounded-2xl border border-red-300/15 bg-red-500/5 px-4 py-3 text-sm text-red-100">
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
    </AuthenticatedLayout>
</template>
