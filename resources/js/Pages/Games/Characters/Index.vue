<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    game: { type: Object, required: true },
    characters: { type: Array, required: true },
});
</script>

<template>
    <Head :title="`Персонажи - ${game.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Персонажи</p>
                    <h1 class="fantasy-title text-3xl">{{ game.name }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">Все персонажи игры на одном экране: состав партии, статус оформления и быстрый переход к листу.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.show', game.id)">
                        <SecondaryButton>Вернуться к игре</SecondaryButton>
                    </Link>
                    <Link :href="route('games.character.edit', game.id)">
                        <PrimaryButton>Мой персонаж</PrimaryButton>
                    </Link>
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
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Состав игры</p>
                                <h2 class="mt-3 text-3xl font-semibold text-amber-50">Все персонажи на одном экране</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">Используйте этот экран как быстрый обзор партии и переход к полному листу любого персонажа.</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Всего персонажей</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ characters.length }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">С аватаром</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ characters.filter((character) => character.avatar_url).length }}</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Без происхождения</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">{{ characters.filter((character) => !character.origin).length }}</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Для мастера</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">Этот экран удобно использовать как быстрый контроль готовности партии перед сессией.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Действие</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">Чтобы изменить своего персонажа, откройте редактор через кнопку «Мой персонаж».</p>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="rounded-[1.75rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="fantasy-kicker">Список персонажей</p>
                            <h2 class="mt-2 text-2xl font-semibold text-amber-50">Все игровые листы</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Откройте лист, чтобы посмотреть характеристики, навыки и инвентарь персонажа.</p>
                        </div>
                    </div>

                    <div v-if="characters.length === 0" class="mt-5 rounded-2xl border border-dashed border-stone-700/60 bg-stone-900/45 px-4 py-4 text-sm text-stone-500">
                        В игре пока нет созданных персонажей.
                    </div>

                    <div v-else class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="character in characters" :key="character.id" class="rounded-[1.4rem] border border-stone-700/50 bg-stone-900/80 p-5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/25 hover:shadow-[0_0_40px_rgba(251,191,36,0.08)]">
                            <div class="flex items-start gap-4">
                                <div v-if="character.avatar_url" class="h-24 w-24 overflow-hidden rounded-2xl border border-white/10 bg-stone-950/60">
                                    <img :src="character.avatar_url" alt="Аватар персонажа" class="h-full w-full object-cover" />
                                </div>
                                <div v-else class="grid h-24 w-24 place-items-center rounded-2xl border border-dashed border-stone-600/50 bg-stone-950/50 text-xs uppercase tracking-[0.2em] text-stone-500">
                                    Hero
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-lg font-semibold text-amber-50">{{ character.name }}</h3>
                                    <p v-if="character.origin" class="mt-1 text-sm text-stone-400">{{ character.origin }}</p>
                                    <p v-else class="mt-1 text-sm text-stone-500">Происхождение не указано</p>
                                    <div class="mt-3 text-sm text-stone-300">
                                        <div>{{ character.user.name }}</div>
                                        <div class="text-stone-500">{{ character.user.email }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <Link :href="route('games.characters.show', [game.id, character.id])">
                                    <PrimaryButton>Открыть лист</PrimaryButton>
                                </Link>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
