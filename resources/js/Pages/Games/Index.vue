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
    games: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const showCreateModal = ref(false);

const form = useForm({
    name: '',
    description: '',
});

const gmGamesCount = computed(() => props.games.filter((game) => game.role === 'gm' || game.role === 'co_gm').length);
const playerGamesCount = computed(() => props.games.filter((game) => game.role === 'player').length);

const submit = () => {
    form.post(route('games.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Мои игры" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Игры</p>
                    <h1 class="fantasy-title text-3xl">Мои игры</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Все кампании, в которых вы ведёте игру или участвуете как игрок.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="rounded-full border border-white/10 bg-white/[0.05] px-4 py-2 text-sm text-stone-300">
                        Всего игр: <span class="font-semibold text-stone-100">{{ games.length }}</span>
                    </div>
                    <PrimaryButton @click="showCreateModal = true">
                        Создать игру
                    </PrimaryButton>
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
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Каталог кампаний</p>
                                <h2 class="mt-3 text-3xl font-semibold text-amber-50">Игровые миры в одном месте</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">
                                    Открывайте кампании, переключайтесь между ролями и быстро заходите в нужную игру без перегруженного списка.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Всего игр</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ games.length }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">GM / co-GM</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ gmGamesCount }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Игрок</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ playerGamesCount }}</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Для старта</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Создайте новую игру, если начинаете кампанию с нуля, или откройте существующую, чтобы продолжить настройку мира.
                                </p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Навигация</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Каждая карточка игры ведёт прямо в центр управления кампанией.
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="fantasy-kicker">Список игр</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Ваши кампании</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">
                                Откройте игру, чтобы перейти к участникам, сессиям, персонажам и игровому контенту.
                            </p>
                        </div>
                        <PrimaryButton @click="showCreateModal = true">
                            Создать игру
                        </PrimaryButton>
                    </div>

                    <div
                        v-if="page.props.flash.success"
                        class="mt-5 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <div v-if="games.length === 0" class="mt-5 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                        У вас пока нет игр. Создайте первую кампанию и настройте её под свою партию.
                    </div>

                    <div v-else class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Link
                            v-for="game in games"
                            :key="game.id"
                            :href="route('games.show', game.id)"
                            class="group block rounded-[1.5rem] border border-stone-700/50 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.12),transparent_12rem),rgba(18,18,16,0.92)] p-5 transition duration-300 hover:-translate-y-1 hover:border-amber-300/30 hover:shadow-[0_26px_60px_rgba(0,0,0,0.32)]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-200/70">Кампания</p>
                                    <h3 class="mt-3 text-xl font-semibold text-amber-50">{{ game.name }}</h3>
                                </div>
                                <div class="rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-300">
                                    {{ game.role_label }}
                                </div>
                            </div>

                            <p v-if="game.description" class="mt-4 line-clamp-4 text-sm leading-6 text-stone-300">
                                {{ game.description }}
                            </p>
                            <p v-else class="mt-4 text-sm leading-6 text-stone-500">
                                Описание пока не заполнено.
                            </p>

                            <div class="mt-5 grid gap-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Владелец</div>
                                    <div class="mt-2 text-sm font-medium text-stone-100">{{ game.owner.name }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <div class="text-[11px] uppercase tracking-[0.18em] text-stone-500">Участников</div>
                                    <div class="mt-2 text-sm font-medium text-stone-100">{{ game.member_count }}</div>
                                </div>
                            </div>

                            <div class="mt-5 flex items-center justify-between text-sm">
                                <span class="text-stone-500 transition group-hover:text-stone-300">Открыть кампанию</span>
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-amber-200/25 bg-amber-400/10 text-lg text-amber-100 transition group-hover:translate-x-1">
                                    →
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-amber-50">Создать игру</h2>
                <p class="mt-2 text-sm leading-6 text-stone-400">
                    После создания вы автоматически становитесь GM этой кампании.
                </p>

                <form class="mt-6 space-y-5" @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Название игры" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-2 block w-full"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Описание" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="fantasy-textarea mt-2 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton @click="showCreateModal = false">Отмена</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            Создать игру
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
