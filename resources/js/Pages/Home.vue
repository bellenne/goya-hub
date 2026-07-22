<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ThemeIcon from '@/Components/ThemeIcon.vue';
import { Head, Link } from '@inertiajs/vue3';

const commandCards = [
    {
        kicker: 'Игры',
        title: 'Кампании и роли',
        text: 'Открывайте миры, где вы мастер, со-мастер или игрок.',
        icon: '/storage/ui/icons/Sessions.png',
        href: () => route('games.index'),
    },
    {
        kicker: 'Сессии',
        title: 'Игровой стол',
        text: 'Переходите из кампании к активной сцене, участникам и подготовке.',
        icon: '/storage/ui/icons/Settings.png',
        iconName: 'management',
        href: () => route('games.index'),
    },
    {
        kicker: 'Контент',
        title: 'База мира',
        text: 'Персонажи, НПС, предметы, фоны и тикеты живут внутри выбранной игры.',
        icon: '/storage/ui/icons/Notes.png',
        href: () => route('games.index'),
    },
];
</script>

<template>
    <Head title="Главная" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="fantasy-kicker">Панель мастера</p>
                    <h1 class="fantasy-title text-3xl">Центр управления</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-400">
                        Рабочий вход в кампании, сессии и игровые материалы.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('games.index')">
                        <PrimaryButton>Открыть игры</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="theme-page">
            <div class="theme-stack">
                <section class="theme-panel">
                    <div class="theme-panel-head">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="gm-panel-icon">
                                <ThemeIcon src="/storage/ui/icons/Viewing.png" name="viewing" />
                            </span>
                            <div class="min-w-0">
                                <p class="gm-kicker">Обзор платформы</p>
                                <h2 class="theme-panel-title">Рабочий стол GoYa Table</h2>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Link :href="route('games.index')">
                                <SecondaryButton>Все кампании</SecondaryButton>
                            </Link>
                        </div>
                    </div>

                    <div class="relative z-10 mt-5 grid gap-4 md:grid-cols-3">
                        <article class="theme-list-row">
                            <p class="theme-stat-label">Игры</p>
                            <p class="mt-2 text-sm leading-6 text-[#d7c5a4]">Кампании, роли и доступные миры.</p>
                        </article>
                        <article class="theme-list-row">
                            <p class="theme-stat-label">Сессии</p>
                            <p class="mt-2 text-sm leading-6 text-[#d7c5a4]">Лобби и активные игровые столы.</p>
                        </article>
                        <article class="theme-list-row">
                            <p class="theme-stat-label">Контент</p>
                            <p class="mt-2 text-sm leading-6 text-[#d7c5a4]">Персонажи, НПС, предметы и фоны внутри игры.</p>
                        </article>
                    </div>
                </section>

                <section class="theme-grid theme-grid-main">
                    <div class="theme-panel">
                        <div class="theme-panel-head">
                            <div>
                                <p class="gm-kicker">Маршруты</p>
                                <h2 class="theme-panel-title">Основные разделы</h2>
                            </div>
                        </div>

                        <div class="theme-list-grid">
                            <Link
                                v-for="card in commandCards"
                                :key="card.title"
                                :href="card.href()"
                                class="theme-card theme-card-interactive block"
                            >
                                <div class="flex items-start gap-3">
                                    <span class="gm-panel-icon">
                                        <ThemeIcon :src="card.icon" :name="card.iconName ?? ''" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="gm-kicker">{{ card.kicker }}</p>
                                        <h3 class="mt-1 text-lg font-semibold text-[#fff1c8]">{{ card.title }}</h3>
                                        <p class="mt-3 text-sm leading-6 text-[#b8a685]">{{ card.text }}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <aside class="theme-panel">
                        <div class="theme-panel-head">
                            <div>
                                <p class="gm-kicker">Порядок работы</p>
                                <h2 class="theme-panel-title">От мира к сцене</h2>
                            </div>
                        </div>
                        <div class="theme-list">
                            <div class="theme-list-row">
                                <p class="theme-stat-label">1. Кампания</p>
                                <p class="mt-2 text-sm leading-6 text-[#d7c5a4]">Создайте игру или откройте существующую.</p>
                            </div>
                            <div class="theme-list-row">
                                <p class="theme-stat-label">2. Материалы</p>
                                <p class="mt-2 text-sm leading-6 text-[#d7c5a4]">Подготовьте персонажей, НПС, предметы и фоны.</p>
                            </div>
                            <div class="theme-list-row">
                                <p class="theme-stat-label">3. Сессия</p>
                                <p class="mt-2 text-sm leading-6 text-[#d7c5a4]">Запустите стол и ведите сцену из панели мастера.</p>
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
