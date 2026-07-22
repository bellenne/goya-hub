<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sidebarOpen = ref(false);

const userInitials = computed(() => (page.props.auth?.user?.name || '?').slice(0, 2));

const navItems = [
    {
        label: 'Главная',
        href: () => route('home'),
        active: () => route().current('home'),
        icon: '/storage/ui/icons/Viewing.png',
    },
    {
        label: 'Игры',
        href: () => route('games.index'),
        active: () => route().current('games.*'),
        icon: '/storage/ui/icons/Sessions.png',
    },
];
</script>

<template>
    <div class="classic-shell gm-shell gm-app-shell">
        <button
            type="button"
            class="gm-sidebar-scrim classic-sidebar-scrim"
            :class="{ 'gm-sidebar-scrim-open classic-sidebar-scrim-open': sidebarOpen }"
            aria-label="Закрыть меню"
            @click="sidebarOpen = false"
        />

        <aside
            class="gm-sidebar classic-sidebar"
            :class="{ 'gm-sidebar-open classic-sidebar-open': sidebarOpen }"
        >
            <Link :href="route('home')" class="gm-sidebar-brand classic-brand" @click="sidebarOpen = false">
                <span class="gm-sidebar-mark classic-brand-mark">
                    <ApplicationLogo class="h-11 w-auto" />
                </span>
                <span class="min-w-0">
                    <span class="gm-kicker classic-kicker block">GoYa Table</span>
                    <span class="gm-sidebar-title classic-brand-title block">Книга мастера</span>
                </span>
            </Link>

            <nav class="gm-sidebar-nav classic-nav">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href()"
                    class="gm-sidebar-link classic-nav-link"
                    :class="{ 'gm-sidebar-link-active classic-nav-link-active': item.active() }"
                    @click="sidebarOpen = false"
                >
                    <span class="gm-sidebar-link-icon">
                        <img :src="item.icon" alt="" class="classic-nav-icon" />
                    </span>
                    <span class="min-w-0">
                        <span class="block">{{ item.label }}</span>
                        <span class="gm-sidebar-link-description">{{ item.label === 'Игры' ? 'Кампании и миры' : 'Обзор' }}</span>
                    </span>
                </Link>
            </nav>

            <div class="gm-sidebar-note classic-sidebar-panel mt-auto">
                <div class="flex items-center gap-3">
                    <span class="gm-sidebar-note-icon classic-note-icon">
                        <img src="/storage/ui/icons/Notes.png" alt="" class="h-5 w-5" />
                    </span>
                    <div class="gm-kicker classic-kicker">Рабочий стол</div>
                </div>
                <p class="mt-2 text-sm leading-6 text-[#b9a98c]">
                    Подготовка кампаний, персонажей, заметок и сессий собрана в одном рабочем пространстве.
                </p>
            </div>
        </aside>

        <div class="gm-workspace classic-workspace">
            <header class="gm-topbar classic-topbar">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="gm-menu-button classic-icon-button lg:hidden"
                        aria-label="Открыть меню"
                        @click="sidebarOpen = true"
                    >
                        <span class="block h-0.5 w-5 bg-current" />
                        <span class="mt-1.5 block h-0.5 w-5 bg-current" />
                        <span class="mt-1.5 block h-0.5 w-5 bg-current" />
                    </button>

                    <div class="min-w-0">
                        <div class="gm-topbar-title">Панель Мастера</div>
                        <div class="gm-topbar-subtitle">Управление игрой и миром</div>
                    </div>
                </div>

                <div class="gm-topbar-actions classic-topbar-actions">
                    <label class="gm-search classic-search">
                        <img src="/storage/ui/icons/Search.png" alt="" class="h-4 w-4 opacity-80" />
                        <input type="search" placeholder="Поиск по платформе" />
                    </label>

                    <button type="button" class="gm-icon-button classic-icon-button" aria-label="Уведомления">
                        <img src="/storage/ui/icons/Notifications.png" alt="" class="h-5 w-5" />
                    </button>

                    <Dropdown align="right" width="48" content-classes="gm-dropdown-panel classic-dropdown-panel">
                        <template #trigger>
                            <button type="button" class="gm-profile-button classic-profile-button">
                                <span class="gm-profile-avatar classic-profile-avatar">{{ userInitials }}</span>
                                <span class="hidden min-w-0 text-left sm:block">
                                    <span class="gm-kicker classic-kicker block">Профиль</span>
                                    <span class="block truncate text-sm font-semibold text-[#f2dfb7]">{{ $page.props.auth.user.name }}</span>
                                </span>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Настройки</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Выйти</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <section v-if="$slots.header" class="classic-header-shell">
                <div class="gm-panel classic-header-panel">
                    <slot name="header" />
                </div>
            </section>

            <main class="gm-main classic-main">
                <slot />
            </main>
        </div>
    </div>
</template>
