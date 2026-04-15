<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Главная" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Главная</p>
                    <h1 class="fantasy-title text-3xl">Центр управления партией</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Основной вход в приложение: быстрый переход к играм, сессиям и ключевым игровым сущностям.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.index')">
                        <PrimaryButton>Открыть игры</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="px-4 pb-10 pt-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="relative overflow-hidden rounded-[2rem] border border-amber-300/15 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.16),transparent_24rem),radial-gradient(circle_at_bottom_right,rgba(45,212,191,0.14),transparent_24rem),linear-gradient(145deg,rgba(28,25,23,0.98),rgba(12,10,9,0.94))] p-6 shadow-[0_30px_120px_rgba(0,0,0,0.42)] ring-1 ring-white/5 sm:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:38px_38px] opacity-30" />
                    <div class="relative grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                        <div class="space-y-5">
                            <div class="rounded-[1.5rem] border border-white/10 bg-stone-950/45 p-5 backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200/80">Игровая база</p>
                                <h2 class="mt-3 text-3xl font-semibold text-amber-50">Всё важное под рукой</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-300">
                                    Отсюда удобно начать работу: открыть свои игры, перейти к активным сессиям и быстро вернуться к контенту кампании.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Игры</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">GM</p>
                                    <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">Кампании и состав</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Сессии</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">RT</p>
                                    <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">Realtime сцена</p>
                                </article>
                                <article class="rounded-[1.35rem] border border-white/10 bg-white/[0.05] p-4 backdrop-blur">
                                    <p class="text-sm text-stone-400">Контент</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">NPC</p>
                                    <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-500">Фоны и предметы</p>
                                </article>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-amber-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-amber-50">Быстрый старт</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Если у вас уже есть игра, переходите к списку игр. Если нет, создайте новую кампанию и настройте шаблон листа персонажа.
                                </p>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-300/15 bg-stone-950/55 p-5 backdrop-blur">
                                <p class="text-sm font-semibold text-teal-50">Текущий стек</p>
                                <p class="mt-3 text-sm leading-6 text-stone-400">
                                    Laravel, Vue 3, PostgreSQL, Redis и Reverb уже собраны в едином игровом интерфейсе.
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-3">
                    <article class="rounded-[1.6rem] border border-amber-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                        <p class="fantasy-kicker">Игры</p>
                        <h2 class="mt-2 text-2xl font-semibold text-amber-50">Кампании и участники</h2>
                        <p class="mt-3 text-sm leading-6 text-stone-400">
                            Управление составом игры, ролями, приглашениями и общим входом в кампанию.
                        </p>
                        <Link :href="route('games.index')" class="mt-5 inline-flex">
                            <PrimaryButton>Перейти к играм</PrimaryButton>
                        </Link>
                    </article>

                    <article class="rounded-[1.6rem] border border-teal-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                        <p class="fantasy-kicker">Сессии</p>
                        <h2 class="mt-2 text-2xl font-semibold text-teal-50">Игровой стол</h2>
                        <p class="mt-3 text-sm leading-6 text-stone-400">
                            Активная сцена, фон, NPC, говорящий, броски кубиков и синхронизация игроков в realtime.
                        </p>
                        <Link :href="route('games.index')" class="mt-5 inline-flex">
                            <SecondaryButton>Открыть через игру</SecondaryButton>
                        </Link>
                    </article>

                    <article class="rounded-[1.6rem] border border-violet-300/15 bg-stone-950/60 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.34)] ring-1 ring-white/5 backdrop-blur">
                        <p class="fantasy-kicker">Контент</p>
                        <h2 class="mt-2 text-2xl font-semibold text-violet-50">Подготовка мира</h2>
                        <p class="mt-3 text-sm leading-6 text-stone-400">
                            NPC, предметы, фоны и шаблоны листов персонажей собираются внутри конкретной игры.
                        </p>
                        <Link :href="route('games.index')" class="mt-5 inline-flex">
                            <SecondaryButton>Открыть игры</SecondaryButton>
                        </Link>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
